<?php

namespace App\Livewire\Participant;

use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Services\QuizAttemptSnapshotService;
use Livewire\Component;

class QuizDone extends Component
{
    public string $token;

    private const SESSION_ATTEMPT_KEY_PREFIX = 'quiz_attempt_id_for_token_';

    public bool $showResult = false;

    public ?array $summary = null;

    public function mount(string $token): void
    {
        $this->token = $token;

        $link = QuizLink::query()
            ->with(['quiz:id,show_result_to_participant', 'attempt'])
            ->where('token', $token)
            ->first();

        if (! $link) {
            return;
        }

        $attempt = $link->usage_type === 'multi'
            ? $this->getAttemptFromSession($link)
            : $link->attempt;

        if (! $attempt || ! in_array((string) $attempt->status, ['submitted', 'auto_submitted'], true)) {
            return;
        }

        if (! $this->shouldShowResult($link, $attempt)) {
            return;
        }

        $result = QuizResult::query()
            ->where('quiz_attempt_id', $attempt->id)
            ->first();

        if (! $result) {
            return;
        }

        $this->showResult = true;
        $this->summary = [
            'score_percentage' => number_format((float) $result->score_percentage, 2, '.', ''),
            'correct_answers' => (int) $result->correct_answers,
            'wrong_answers' => (int) $result->wrong_answers,
            'unanswered_answers' => (int) $result->unanswered_answers,
            'total_questions' => (int) $result->total_questions,
            'grade_letter' => (string) $result->grade_letter,
            'grade_label' => (string) $result->grade_label,
        ];
    }

    private function shouldShowResult(QuizLink $link, QuizAttempt $attempt): bool
    {
        $snapshot = app(QuizAttemptSnapshotService::class)->validSnapshot($attempt->quiz_snapshot);
        $snapshotQuiz = is_array($snapshot) ? ($snapshot['quiz'] ?? []) : [];

        if (array_key_exists('show_result_to_participant', $snapshotQuiz)) {
            return (bool) $snapshotQuiz['show_result_to_participant'];
        }

        return (bool) ($link->quiz?->show_result_to_participant ?? false);
    }

    private function getAttemptFromSession(QuizLink $link): ?QuizAttempt
    {
        $attemptId = session()->get(self::SESSION_ATTEMPT_KEY_PREFIX.$link->token);
        if (! is_int($attemptId) || $attemptId <= 0) {
            return null;
        }

        return QuizAttempt::query()
            ->where('id', $attemptId)
            ->where('quiz_link_id', $link->id)
            ->first();
    }

    public function render()
    {
        return view('livewire.participant.quiz-done');
    }
}
