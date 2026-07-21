<?php

namespace App\Services\Discord;

use App\Support\ParticipantWorkStartFormatter;
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

        $row = $this->loadResultRow($quizResultId);
        if (! $row) {
            return;
        }

        $payload = $this->buildPayload($row);
        $urls = $this->webhookUrlsForRow($row);
        foreach ($urls as $url) {
            if ($this->wasSentSuccessfully($quizResultId, $url)) {
                continue;
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
    private function webhookUrlsForRow(object $row): array
    {
        $userRaw = (string) ($row->discord_webhook_url ?? '');
        $defaultRaw = (string) env('DISCORD_WEBHOOK_URL', '');

        $userUrls = DiscordWebhookUrlParser::parseList($userRaw);
        $defaultUrls = DiscordWebhookUrlParser::parseList($defaultRaw);

        // Prefer user-configured URLs; fall back to env default.
        return $userUrls !== [] ? $userUrls : $defaultUrls;
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
                'quizzes.division as quiz_division',
                'quiz_attempts.participant_name',
                'quiz_attempts.participant_applied_for',
                'quiz_attempts.participant_age',
                'quiz_attempts.participant_height_cm',
                'quiz_attempts.participant_weight_kg',
                'quiz_attempts.participant_last_job',
                'quiz_attempts.participant_last_company',
                'quiz_attempts.participant_last_job_started_at',
                'quiz_attempts.participant_current_domicile',
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

    private function wasSentSuccessfully(int $quizResultId, string $url): bool
    {
        if (! Schema::hasTable('discord_webhook_logs')) {
            return false;
        }

        return DB::table('discord_webhook_logs')
            ->where('quiz_result_id', $quizResultId)
            ->where('webhook_url', $url)
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

        if ((string) $row->quiz_division === 'hr') {
            array_splice($fields, 1, 1);

            $age = $row->participant_age !== null
                ? (int) $row->participant_age.' tahun'
                : '-';
            $height = $row->participant_height_cm !== null
                ? number_format((float) $row->participant_height_cm, 2, '.', '').' cm'
                : '-';
            $weight = $row->participant_weight_kg !== null
                ? number_format((float) $row->participant_weight_kg, 2, '.', '').' kg'
                : '-';

            array_splice($fields, 1, 0, [[
                'name' => 'Usia',
                'value' => $age,
                'inline' => true,
            ], [
                'name' => 'Tinggi / Berat',
                'value' => $height.' / '.$weight,
                'inline' => true,
            ], [
                'name' => 'Pekerjaan Terakhir',
                'value' => (string) ($row->participant_last_job ?: '-'),
                'inline' => true,
            ], [
                'name' => 'Perusahaan Terakhir',
                'value' => (string) ($row->participant_last_company ?: '-'),
                'inline' => true,
            ], [
                'name' => 'Sejak Kapan Bekerja',
                'value' => ParticipantWorkStartFormatter::format($row->participant_last_job_started_at),
                'inline' => true,
            ], [
                'name' => 'Domisili',
                'value' => (string) ($row->participant_current_domicile ?: '-'),
                'inline' => false,
            ]]);
        }

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
            'A' => 0x16A34A,
            'B' => 0x2563EB,
            'C' => 0xCA8A04,
            'D' => 0xEA580C,
            default => 0xDC2626,
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
