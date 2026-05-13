<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('quiz-links:send-expired-summaries', function () {
    $now = now();

    $linkIds = DB::table('quiz_links')
        ->leftJoin('discord_link_summary_logs', function ($join) {
            $join
                ->on('discord_link_summary_logs.quiz_link_id', '=', 'quiz_links.id')
                ->where('discord_link_summary_logs.is_success', '=', true);
        })
        ->where('quiz_links.usage_type', 'multi')
        ->whereNotNull('quiz_links.expires_at')
        ->where('quiz_links.expires_at', '<=', $now)
        ->whereNull('discord_link_summary_logs.quiz_link_id')
        ->orderBy('quiz_links.expires_at')
        ->limit(200)
        ->pluck('quiz_links.id')
        ->map(fn ($v) => (int) $v)
        ->all();

    foreach ($linkIds as $linkId) {
        DB::transaction(function () use ($linkId, $now): void {
            $link = DB::table('quiz_links')
                ->where('id', $linkId)
                ->lockForUpdate()
                ->first();

            if (! $link) {
                return;
            }

            if ((string) $link->status !== 'expired') {
                DB::table('quiz_links')
                    ->where('id', $linkId)
                    ->update([
                        'status' => 'expired',
                        'expired_at' => $link->expired_at ?? $now,
                        'updated_at' => $now,
                    ]);
            }

            $attemptIds = DB::table('quiz_attempts')
                ->where('quiz_link_id', $linkId)
                ->whereIn('status', ['not_started', 'in_progress'])
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            if ($attemptIds !== []) {
                DB::table('quiz_attempts')
                    ->whereIn('id', $attemptIds)
                    ->update([
                        'status' => 'expired',
                        'submitted_at' => $now,
                        'updated_at' => $now,
                    ]);
            }
        });

        app(\App\Services\Discord\DiscordLinkSummaryWebhookService::class)->sendForQuizLinkId($linkId);
    }
})->purpose('Kirim ringkasan Discord untuk link multi-use yang sudah expired.');

Schedule::command('quiz-links:send-expired-summaries')
    ->everyMinute()
    ->withoutOverlapping();
