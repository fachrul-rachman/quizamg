<?php

namespace App\Jobs;

use App\Models\QuizAttempt;
use App\Services\Discord\DiscordResultWebhookService;
use App\Services\Pdf\ResultPdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeliverQuizResult implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 3;

    public function __construct(public int $resultId, public int $attemptId) {}

    public function handle(ResultPdfService $pdf, DiscordResultWebhookService $discord): void
    {
        try {
            $pdf->generateForResultId($this->resultId);
        } catch (\Throwable $e) {
            Log::error('result pdf generation threw', [
                'quiz_result_id' => $this->resultId,
                'error' => $e->getMessage(),
            ]);
        }

        $attempt = QuizAttempt::query()->with('quizLink:id,usage_type')
            ->find($this->attemptId, ['id', 'quiz_link_id']);

        if ($attempt && $attempt->quizLink?->usage_type !== 'multi') {
            $discord->sendForResultId($this->resultId);
        }
    }
}
