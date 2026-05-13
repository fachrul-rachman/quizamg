<?php

namespace App\Services\Discord;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DiscordResultWebhookService
{
    public function sendForResultId(int $quizResultId): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        if ($this->wasSentSuccessfully($quizResultId)) {
            return;
        }

        $row = $this->loadResultRow($quizResultId);
        if (! $row) {
            return;
        }

        $payload = $this->buildPayload($row);
        $url = $this->webhookUrlForRow($row);
        if ($url === '') {
            return;
        }

        try {
            $response = Http::timeout(20)->post($url, $payload);

            $this->storeLog(
                $quizResultId,
                $url,
                $payload,
                $response->status(),
                $response->body(),
                $response->successful()
            );

            if (! $response->successful()) {
                Log::error('discord webhook response not successful', [
                    'quiz_result_id' => $quizResultId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->storeLog(
                $quizResultId,
                $url,
                $payload,
                null,
                $e->getMessage(),
                false
            );

            Log::error('discord webhook request failed', [
                'quiz_result_id' => $quizResultId,
                'error' => $e->getMessage(),
            ]);
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

    private function webhookUrlForRow(object $row): string
    {
        $userUrl = trim((string) ($row->discord_webhook_url ?? ''));
        if ($userUrl !== '') {
            return $userUrl;
        }

        return trim((string) env('DISCORD_WEBHOOK_URL', ''));
    }

    private function loadResultRow(int $quizResultId): ?object
    {
        return DB::table('quiz_results')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_results.quiz_attempt_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_results.quiz_id')
            ->join('users', 'users.id', '=', 'quizzes.created_by')
            ->leftJoin('result_pdfs', 'result_pdfs.quiz_result_id', '=', 'quiz_results.id')
            ->where('quiz_results.id', $quizResultId)
            ->select([
                'quizzes.title as quiz_title',
                'quiz_attempts.participant_name',
                'quiz_attempts.participant_applied_for',
                'users.discord_webhook_url',
                'quiz_results.correct_answers',
                'quiz_results.total_questions',
                'quiz_results.wrong_answers',
                'quiz_results.unanswered_answers',
                'quiz_results.score_percentage',
                'quiz_results.grade_letter',
                'quiz_results.grade_label',
                'quiz_results.result_status',
                'quiz_results.calculated_at',
                'result_pdfs.google_drive_url',
            ])
            ->first();
    }

    private function wasSentSuccessfully(int $quizResultId): bool
    {
        if (! Schema::hasTable('discord_webhook_logs')) {
            return false;
        }

        return DB::table('discord_webhook_logs')
            ->where('quiz_result_id', $quizResultId)
            ->where('is_success', true)
            ->exists();
    }

    private function buildPayload(object $row): array
    {
        $statusText = (string) $row->result_status === 'auto_submitted'
            ? 'Auto Submitted'
            : 'Submitted';

        $fields = [
            [
                'name' => 'Nama',
                'value' => (string) $row->participant_name,
                'inline' => true,
            ],
            [
                'name' => 'Jabatan',
                'value' => (string) $row->participant_applied_for,
                'inline' => true,
            ],
            [
                'name' => 'Nama Tes',
                'value' => (string) $row->quiz_title,
                'inline' => false,
            ],
            [
                'name' => 'Tanggal Tes',
                'value' => (string) $row->calculated_at,
                'inline' => true,
            ],
            [
                'name' => 'Status',
                'value' => $statusText,
                'inline' => true,
            ],
            [
                'name' => 'Score',
                'value' => sprintf(
                    '%d / %d (%.2f%%) - Grade %s',
                    (int) $row->correct_answers,
                    (int) $row->total_questions,
                    (float) $row->score_percentage,
                    (string) $row->grade_letter
                ),
                'inline' => false,
            ],
            [
                'name' => 'Keterangan',
                'value' => sprintf(
                    '%s | Salah: %d | Kosong: %d',
                    (string) $row->grade_label,
                    (int) $row->wrong_answers,
                    (int) $row->unanswered_answers
                ),
                'inline' => false,
            ],
        ];

        if (is_string($row->google_drive_url) && $row->google_drive_url !== '') {
            $fields[] = [
                'name' => 'File Hasil',
                'value' => '[Buka File di Google Drive]('.$row->google_drive_url.')',
                'inline' => false,
            ];
        }

        return [
            'embeds' => [[
                'title' => 'Hasil Tes Seleksi Selesai',
                'description' => 'Ringkasan hasil seleksi peserta telah selesai diproses.',
                'color' => $this->embedColor((string) $row->grade_letter),
                'fields' => $fields,
                'footer' => [
                    'text' => 'PT. NUM Assessment System',
                ],
                'timestamp' => now()->toIso8601String(),
            ]],
        ];
    }

    private function embedColor(string $gradeLetter): int
    {
        return match (strtoupper($gradeLetter)) {
            'A' => 0x16a34a,
            'B' => 0x2563eb,
            'C' => 0xca8a04,
            'D' => 0xea580c,
            default => 0xdc2626,
        };
    }

    private function storeLog(
        int $quizResultId,
        string $url,
        array $payload,
        ?int $responseStatusCode,
        ?string $responseBody,
        bool $isSuccess
    ): void {
        if (! Schema::hasTable('discord_webhook_logs')) {
            return;
        }

        DB::table('discord_webhook_logs')->insert([
            'quiz_result_id' => $quizResultId,
            'webhook_url' => $url,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'response_status_code' => $responseStatusCode,
            'response_body' => $responseBody,
            'is_success' => $isSuccess,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
