<?php

namespace App\Services\Discord;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DiscordLinkSummaryWebhookService
{
    private const int DISCORD_MESSAGE_LIMIT = 2000;

    public function sendForQuizLinkId(int $quizLinkId): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $link = $this->loadLinkRow($quizLinkId);
        if (! $link) {
            return;
        }

        if ((string) $link->usage_type !== 'multi') {
            return;
        }

        if (! is_string($link->expires_at) || trim($link->expires_at) === '') {
            return;
        }

        $expiresAt = CarbonImmutable::parse((string) $link->expires_at);
        if (CarbonImmutable::now()->lt($expiresAt)) {
            return;
        }

        $attemptRows = $this->loadCompletedAttemptRows($quizLinkId, $expiresAt);

        $payloadChunks = $this->buildPayloadChunks($link, $expiresAt, $attemptRows);
        if ($payloadChunks === []) {
            return;
        }

        $urls = $this->webhookUrlsForLink($link);
        foreach ($urls as $url) {
            if ($this->wasSentSuccessfully($quizLinkId, $url)) {
                continue;
            }

            $finalStatus = null;
            $finalBody = null;
            $allOk = true;

            foreach ($payloadChunks as $payload) {
                try {
                    $response = Http::timeout(20)->post($url, $payload);
                    $finalStatus = $response->status();
                    $finalBody = $response->body();

                    if (! $response->successful()) {
                        $allOk = false;
                        Log::error('discord link summary webhook response not successful', [
                            'quiz_link_id' => $quizLinkId,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    $allOk = false;
                    $finalStatus = null;
                    $finalBody = $e->getMessage();
                    Log::error('discord link summary webhook request failed', [
                        'quiz_link_id' => $quizLinkId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->storeLog($quizLinkId, $url, $payloadChunks, $finalStatus, $finalBody, $allOk);
        }
    }

    private function isEnabled(): bool
    {
        $enabled = env('DISCORD_WEBHOOK_ENABLED');

        if ($enabled === null) {
            return true;
        }

        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<int, string>
     */
    private function webhookUrlsForLink(object $link): array
    {
        $userUrl = trim((string) ($link->discord_webhook_url ?? ''));
        $defaultUrl = trim((string) env('DISCORD_WEBHOOK_URL', ''));
        $ceoUrl = trim((string) env('DISCORD_WEBHOOK_CEO_URL', ''));

        $primaryUrl = $userUrl !== '' ? $userUrl : $defaultUrl;

        return collect([$primaryUrl, $ceoUrl])
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn (string $v) => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function wasSentSuccessfully(int $quizLinkId, string $url): bool
    {
        if (! Schema::hasTable('discord_link_summary_logs')) {
            return false;
        }

        return DB::table('discord_link_summary_logs')
            ->where('quiz_link_id', $quizLinkId)
            ->where('webhook_url', $url)
            ->where('is_success', true)
            ->exists();
    }

    private function loadLinkRow(int $quizLinkId): ?object
    {
        return DB::table('quiz_links')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_links.quiz_id')
            ->join('users', 'users.id', '=', 'quizzes.created_by')
            ->where('quiz_links.id', $quizLinkId)
            ->select([
                'quiz_links.id',
                'quiz_links.usage_type',
                'quiz_links.expires_at',
                'quizzes.title as quiz_title',
                'users.discord_webhook_url',
            ])
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    private function loadCompletedAttemptRows(int $quizLinkId, CarbonImmutable $expiresAt): Collection
    {
        return DB::table('quiz_attempts')
            ->join('quiz_results', 'quiz_results.quiz_attempt_id', '=', 'quiz_attempts.id')
            ->where('quiz_attempts.quiz_link_id', $quizLinkId)
            ->whereNotNull('quiz_attempts.submitted_at')
            ->where('quiz_attempts.submitted_at', '<=', $expiresAt->toDateTimeString())
            ->orderBy('quiz_attempts.submitted_at')
            ->select([
                'quiz_attempts.participant_name',
                'quiz_results.correct_answers',
                'quiz_results.total_questions',
                'quiz_results.grade_letter',
            ])
            ->get();
    }

    /**
     * @param  Collection<int, object>  $attemptRows
     * @return array<int, array{content:string, allowed_mentions:array{parse:array<int, string>}}>
     */
    private function buildPayloadChunks(object $link, CarbonImmutable $expiresAt, Collection $attemptRows): array
    {
        $totalCompleted = $attemptRows->count();
        $successCount = $attemptRows
            ->filter(fn ($r) => in_array(strtoupper((string) $r->grade_letter), ['A', 'B'], true))
            ->count();

        $rate = $totalCompleted > 0 ? (int) round(($successCount / $totalCompleted) * 100) : 0;

        $lines = [];
        $lines[] = 'Nama Test: '.(string) $link->quiz_title;
        $lines[] = 'Tanggal Expired: '.$expiresAt->format('d/m/y H:i');
        $lines[] = 'List Peserta:';

        if ($attemptRows->isEmpty()) {
            $lines[] = '- (Tidak ada attempt selesai sampai expired)';
        } else {
            foreach ($attemptRows as $r) {
                $correct = (int) $r->correct_answers;
                $total = (int) $r->total_questions;
                $grade = strtoupper((string) $r->grade_letter);
                $name = (string) $r->participant_name;
                $lines[] = '- '.$name.' -- '.$correct.'/'.$total.' Grade '.$grade;
            }
        }

        $lines[] = '-----';
        $lines[] = 'Persentase Keberhasilan (A/B): '.$rate.'% ('.$successCount.' dari '.$totalCompleted.')';

        $fullText = implode("\n", $lines);

        $chunks = $this->splitText($fullText, self::DISCORD_MESSAGE_LIMIT - 50);

        return array_map(fn (string $text) => [
            'content' => $text,
            'allowed_mentions' => ['parse' => []],
        ], $chunks);
    }

    /**
     * @return array<int, string>
     */
    private function splitText(string $text, int $maxLen): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= $maxLen) {
            return [$text];
        }

        $lines = preg_split("/\r?\n/", $text) ?: [$text];

        $chunks = [];
        $current = '';

        foreach ($lines as $line) {
            $line = (string) $line;
            $candidate = $current === '' ? $line : ($current."\n".$line);

            if (mb_strlen($candidate) <= $maxLen) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
                $current = '';
            }

            if (mb_strlen($line) <= $maxLen) {
                $current = $line;
                continue;
            }

            // Fallback: hard-split very long line
            $offset = 0;
            $len = mb_strlen($line);
            while ($offset < $len) {
                $chunks[] = mb_substr($line, $offset, $maxLen);
                $offset += $maxLen;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $payloadChunks
     */
    private function storeLog(
        int $quizLinkId,
        string $url,
        array $payloadChunks,
        ?int $responseStatusCode,
        ?string $responseBody,
        bool $isSuccess
    ): void {
        if (! Schema::hasTable('discord_link_summary_logs')) {
            return;
        }

        DB::table('discord_link_summary_logs')->updateOrInsert(
            ['quiz_link_id' => $quizLinkId, 'webhook_url' => $url],
            [
                'payload_json' => json_encode($payloadChunks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'response_status_code' => $responseStatusCode,
                'response_body' => $responseBody,
                'is_success' => $isSuccess,
                'sent_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
