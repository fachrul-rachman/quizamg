<?php

namespace App\Livewire\Participant;

use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Services\Discord\DiscordResultWebhookService;
use App\Services\GradeService;
use App\Services\Pdf\ResultPdfService;
use App\Support\DeterministicShuffle;
use App\Support\QuestionDifficulty;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuizWork extends Component
{
    public string $token;

    private const SESSION_ATTEMPT_KEY_PREFIX = 'quiz_attempt_id_for_token_';

    public string $state = 'loading';
    public string $title = '';
    public string $participantName = '';
    public string $participantAppliedFor = '';
    public int $secondsRemaining = 0;

    public int $linkId = 0;
    public int $attemptId = 0;
    public int $quizId = 0;
    public int $quizPk = 0;
    public bool $instantFeedbackEnabled = false;
    public bool $difficultyLevelsEnabled = false;
    public bool $shuffleOptions = false;

    public int $step = 1;

    /** @var array<int, int> */
    public array $questionIds = [];

    public ?int $currentQuestionId = null;
    public ?string $currentQuestionText = null;
    public ?string $currentQuestionImagePath = null;
    public ?string $currentQuestionType = null;
    public ?string $currentDifficultyLevel = null;

    /** @var array<int, array{id:int,label:string,text:string,image_path:?string,is_correct:bool}> */
    public array $currentOptions = [];

    public ?int $selectedOptionId = null;
    public ?int $lockedSelectedOptionId = null;
    public string $shortAnswerText = '';
    public ?bool $currentAnswerIsCorrect = null;
    public ?int $currentCorrectOptionId = null;
    public bool $currentAnswerLocked = false;
    public bool $pendingAutoAdvance = false;

    public int $answeredCount = 0;
    public int $totalQuestions = 0;

    /** @var array<int, array{question_id:int, step:int}> */
    public array $skippedQuestionButtons = [];

    private bool $suppressInstantFeedbackLock = false;

    public function mount(string $token): void
    {
        $this->token = $token;

        $link = QuizLink::query()
            ->with(['quiz:id,title,is_active,shuffle_questions,shuffle_options,instant_feedback_enabled,difficulty_levels_enabled', 'attempt'])
            ->where('token', $token)
            ->first();

        if (! $link) {
            $this->state = 'invalid';
            return;
        }

        if (in_array($link->status, ['submitted', 'expired'], true)) {
            $this->state = $link->status === 'submitted' ? 'submitted' : 'expired';
            return;
        }

        if (! $link->quiz || ! $link->quiz->is_active) {
            $this->state = 'unavailable';
            return;
        }

        if ($link->usage_type === 'multi' && $this->isMultiUseExpired($link)) {
            $attempt = $this->getAttemptFromSession($link);
            if ($attempt) {
                $this->linkId = (int) $link->id;
                $this->attemptId = (int) $attempt->id;
                $this->expireAttemptIfMultiUseLinkExpired();
            }

            $this->state = 'expired';
            return;
        }

        $attempt = $link->usage_type === 'multi'
            ? $this->getAttemptFromSession($link)
            : $link->attempt;

        if (! $attempt) {
            $this->redirect('/quiz/'.$token, navigate: false);
            return;
        }

        if ($attempt->status !== 'in_progress') {
            $this->redirect('/quiz/'.$token, navigate: false);
            return;
        }

        $this->title = (string) $link->quiz->title;
        $this->participantName = (string) $attempt->participant_name;
        $this->participantAppliedFor = (string) $attempt->participant_applied_for;

        $this->linkId = (int) $link->id;
        $this->attemptId = (int) $attempt->id;
        $this->quizId = (int) $attempt->quiz_id;
        $this->quizPk = (int) $link->quiz->id;
        $this->instantFeedbackEnabled = (bool) $link->quiz->instant_feedback_enabled;
        $this->difficultyLevelsEnabled = (bool) $link->quiz->difficulty_levels_enabled;
        $this->shuffleOptions = (bool) $link->quiz->shuffle_options;

        $this->secondsRemaining = $this->calculateSecondsRemaining($attempt);
        if ($this->secondsRemaining <= 0) {
            $this->finalizeAutoIfNeeded();
            return;
        }

        $this->questionIds = $this->buildOrderedQuestionIds(
            $this->quizId,
            (bool) $link->quiz->shuffle_questions,
            (bool) $link->quiz->difficulty_levels_enabled,
            $this->attemptId,
            (int) $link->quiz->id
        );
        if ($this->questionIds === []) {
            $this->state = 'no_questions';
            return;
        }

        $this->step = 1;
        if (! $this->moveToFirstUnworkedStep()) {
            $this->finalize('submitted');
            $this->redirect('/quiz/'.$this->token.'/done', navigate: false);
            return;
        }
        $this->loadStep($this->attemptId, $this->quizId, $this->shuffleOptions, $this->quizPk);
        $this->refreshProgress();

        $this->state = 'work';
    }

    public function tick(): void
    {
        if ($this->attemptId <= 0) {
            return;
        }

        $this->expireAttemptIfMultiUseLinkExpired();
        if ($this->state === 'expired') {
            return;
        }

        $attempt = QuizAttempt::query()->find($this->attemptId);
        if (! $attempt) {
            $this->state = 'invalid';
            return;
        }

        $this->secondsRemaining = $this->calculateSecondsRemaining($attempt);
        if ($this->secondsRemaining <= 0) {
            $this->finalizeAutoIfNeeded();
        }
    }

    public function updatedSelectedOptionId(): void
    {
        if ($this->suppressInstantFeedbackLock) {
            return;
        }

        if ($this->currentAnswerLocked && $this->lockedSelectedOptionId !== null) {
            $this->suppressInstantFeedbackLock = true;
            $this->selectedOptionId = (int) $this->lockedSelectedOptionId;
            $this->suppressInstantFeedbackLock = false;
            return;
        }

        $this->lockCurrentMultipleChoiceAnswer();
    }

    public function answerCurrent(): void
    {
        $this->expireAttemptIfMultiUseLinkExpired();
        if ($this->state === 'expired') {
            return;
        }

        if (! $this->currentQuestionId || ! $this->currentQuestionType) {
            return;
        }

        if ($this->attemptId <= 0) {
            return;
        }

        $attempt = QuizAttempt::query()->find($this->attemptId);
        if (! $attempt || (int) $attempt->quiz_link_id !== (int) $this->linkId) {
            return;
        }

        if ($attempt->status !== 'in_progress') {
            return;
        }

        $this->secondsRemaining = $this->calculateSecondsRemaining($attempt);
        if ($this->secondsRemaining <= 0) {
            return;
        }

        if ($this->currentQuestionType === 'multiple_choice') {
            if (! $this->selectedOptionId) {
                throw ValidationException::withMessages(['selectedOptionId' => 'Pilih salah satu opsi.']);
            }

            $selectedOptionId = (int) $this->selectedOptionId;
            $correctOptionId = (int) ($this->currentCorrectOptionId ?? 0);
            $isCorrect = $selectedOptionId !== 0 && $selectedOptionId === $correctOptionId;

            AttemptAnswer::updateOrCreate(
                ['quiz_attempt_id' => $attempt->id, 'question_id' => $this->currentQuestionId],
                [
                    'selected_option_id' => $selectedOptionId,
                    'answer_text' => null,
                    'is_correct' => $isCorrect,
                    'answered_at' => now(),
                    'skipped_at' => null,
                ],
            );

            if ($this->instantFeedbackEnabled) {
                $this->currentAnswerLocked = true;
                $this->currentAnswerIsCorrect = $isCorrect;
                $this->lockedSelectedOptionId = $selectedOptionId;
            }
        } else {
            $text = trim($this->shortAnswerText);
            if ($text === '') {
                throw ValidationException::withMessages(['shortAnswerText' => 'Jawaban wajib diisi.']);
            }

            AttemptAnswer::updateOrCreate(
                ['quiz_attempt_id' => $attempt->id, 'question_id' => $this->currentQuestionId],
                [
                    'selected_option_id' => null,
                    'answer_text' => $text,
                    'answered_at' => now(),
                    'skipped_at' => null,
                ],
            );
        }

        $this->refreshProgress();

        if ($this->instantFeedbackEnabled && $this->currentQuestionType === 'multiple_choice') {
            $this->pendingAutoAdvance = true;
            $this->dispatch('participant-quiz-auto-advance');
            return;
        }

        $this->goToNextWorkStepOrFinalize();
    }

    public function skipCurrent(): void
    {
        $this->expireAttemptIfMultiUseLinkExpired();
        if ($this->state === 'expired') {
            return;
        }

        if ($this->attemptId <= 0 || ! $this->currentQuestionId) {
            return;
        }

        $attempt = QuizAttempt::query()->find($this->attemptId);
        if (! $attempt || (int) $attempt->quiz_link_id !== (int) $this->linkId) {
            return;
        }

        if ($attempt->status !== 'in_progress') {
            return;
        }

        $this->secondsRemaining = $this->calculateSecondsRemaining($attempt);
        if ($this->secondsRemaining <= 0) {
            return;
        }

        AttemptAnswer::updateOrCreate(
            ['quiz_attempt_id' => $attempt->id, 'question_id' => $this->currentQuestionId],
            [
                'selected_option_id' => null,
                'answer_text' => null,
                'is_correct' => false,
                'answered_at' => null,
                'skipped_at' => now(),
            ],
        );

        $this->suppressInstantFeedbackLock = true;
        $this->selectedOptionId = null;
        $this->suppressInstantFeedbackLock = false;
        $this->shortAnswerText = '';
        $this->currentAnswerIsCorrect = null;
        $this->currentAnswerLocked = false;
        $this->lockedSelectedOptionId = null;
        $this->pendingAutoAdvance = false;

        $this->refreshProgress();
        $this->goToNextWorkStepOrFinalize();
    }

    public function goToSkippedQuestion(int $questionId): void
    {
        $this->expireAttemptIfMultiUseLinkExpired();
        if ($this->state === 'expired') {
            return;
        }

        if ($this->pendingAutoAdvance || $this->attemptId <= 0 || $questionId <= 0) {
            return;
        }

        $stepIndex = array_search($questionId, $this->questionIds, true);
        if (! is_int($stepIndex)) {
            return;
        }

        $isSkipped = AttemptAnswer::query()
            ->where('quiz_attempt_id', $this->attemptId)
            ->where('question_id', $questionId)
            ->whereNotNull('skipped_at')
            ->exists();

        if (! $isSkipped) {
            $this->refreshProgress();
            return;
        }

        $this->step = $stepIndex + 1;
        $this->loadStep($this->attemptId, $this->quizId, $this->shuffleOptions, $this->quizPk);
        $this->refreshProgress();
    }

    public function advanceAfterInstantFeedback(): void
    {
        $this->expireAttemptIfMultiUseLinkExpired();
        if ($this->state === 'expired') {
            return;
        }

        if (! $this->pendingAutoAdvance) {
            return;
        }

        $this->pendingAutoAdvance = false;
        $this->goToNextWorkStepOrFinalize();
    }

    private function goToNextWorkStepOrFinalize(): void
    {
        if (! $this->moveToFirstUnworkedStep()) {
            $this->finalize('submitted');
            $this->redirect('/quiz/'.$this->token.'/done', navigate: false);
            return;
        }

        $this->loadStep($this->attemptId, $this->quizId, $this->shuffleOptions, $this->quizPk);
    }

    private function calculateSecondsRemaining(QuizAttempt $attempt): int
    {
        if (! $attempt->started_at) {
            return 0;
        }

        $startedAt = CarbonImmutable::parse($attempt->started_at);
        $deadline = $startedAt->addMinutes((int) $attempt->time_limit_minutes);
        $diff = $deadline->diffInSeconds(CarbonImmutable::now(), false) * -1;
        return max(0, (int) $diff);
    }

    private function refreshProgress(): void
    {
        $this->totalQuestions = count($this->questionIds);
        if ($this->attemptId <= 0 || $this->totalQuestions === 0) {
            $this->answeredCount = 0;
            $this->skippedQuestionButtons = [];
            return;
        }

        $answers = AttemptAnswer::query()
            ->where('quiz_attempt_id', $this->attemptId)
            ->whereIn('question_id', $this->questionIds)
            ->get(['question_id', 'selected_option_id', 'answer_text', 'skipped_at'])
            ->keyBy('question_id');

        $answered = 0;
        $skipped = [];
        foreach ($this->questionIds as $qid) {
            $a = $answers->get($qid);
            if (! $a) {
                continue;
            }

            if ($a->skipped_at) {
                $stepIndex = array_search((int) $qid, $this->questionIds, true);
                if (is_int($stepIndex)) {
                    $skipped[] = [
                        'question_id' => (int) $qid,
                        'step' => $stepIndex + 1,
                    ];
                }
                continue;
            }

            if ($a->selected_option_id) {
                $answered++;
                continue;
            }

            if (is_string($a->answer_text) && trim($a->answer_text) !== '') {
                $answered++;
            }
        }

        $this->answeredCount = $answered;
        $this->skippedQuestionButtons = $skipped;
    }

    private function moveToFirstUnworkedStep(): bool
    {
        if ($this->attemptId <= 0 || $this->questionIds === []) {
            $this->step = 1;
            return true;
        }

        $answers = AttemptAnswer::query()
            ->where('quiz_attempt_id', $this->attemptId)
            ->whereIn('question_id', $this->questionIds)
            ->get(['question_id', 'selected_option_id', 'answer_text', 'skipped_at'])
            ->keyBy('question_id');

        $firstSkippedStep = null;

        foreach ($this->questionIds as $idx => $qid) {
            $a = $answers->get($qid);
            if (! $a) {
                $this->step = $idx + 1;
                return true;
            }

            if ($a->skipped_at) {
                $firstSkippedStep ??= $idx + 1;
                continue;
            }

            if ($a->selected_option_id) {
                continue;
            }

            if (is_string($a->answer_text) && trim($a->answer_text) !== '') {
                continue;
            }

            $this->step = $idx + 1;
            return true;
        }

        if ($firstSkippedStep !== null) {
            $this->step = $firstSkippedStep;
            return true;
        }

        return false;
    }

    /**
     * @return array<int, int>
     */
    private function buildOrderedQuestionIds(
        int $quizId,
        bool $shuffleQuestions,
        bool $difficultyLevelsEnabled,
        int $attemptId,
        int $quizPk
    ): array
    {
        $rows = DB::table('questions')
            ->where('quiz_id', $quizId)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get(['id', 'difficulty_level'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'difficulty_level' => (string) ($row->difficulty_level ?? QuestionDifficulty::DEFAULT),
            ])
            ->all();

        if ($rows === []) {
            return [];
        }

        if (! $difficultyLevelsEnabled) {
            $ids = array_map(fn (array $row) => (int) $row['id'], $rows);
            if (! $shuffleQuestions || count($ids) <= 1) {
                return $ids;
            }

            $seed = $this->seedFromAttempt($attemptId, $quizPk);
            return DeterministicShuffle::shuffle($ids, $seed);
        }

        $ids = [];
        foreach (QuestionDifficulty::LEVELS as $difficultyLevel) {
            $bucket = array_values(array_filter(
                $rows,
                fn (array $row) => (($row['difficulty_level'] ?: QuestionDifficulty::DEFAULT) === $difficultyLevel)
            ));

            $bucketIds = array_map(fn (array $row) => (int) $row['id'], $bucket);
            if ($shuffleQuestions && count($bucketIds) > 1) {
                $bucketIds = DeterministicShuffle::shuffle(
                    $bucketIds,
                    $this->seedForDifficulty($attemptId, $quizPk, $difficultyLevel)
                );
            }

            array_push($ids, ...$bucketIds);
        }

        if ($ids !== []) {
            return $ids;
        }

        return array_map(fn (array $row) => (int) $row['id'], $rows);
    }

    private function seedFromAttempt(int $attemptId, int $quizPk): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizPk, true);
        $unpacked = unpack('N', substr($hash, 0, 4));
        return (int) ($unpacked[1] ?? 1);
    }

    private function seedForQuestion(int $attemptId, int $quizPk, int $questionId): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizPk.':q:'.$questionId, true);
        $unpacked = unpack('N', substr($hash, 0, 4));
        return (int) ($unpacked[1] ?? 1);
    }

    private function seedForDifficulty(int $attemptId, int $quizPk, string $difficultyLevel): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizPk.':difficulty:'.$difficultyLevel, true);
        $unpacked = unpack('N', substr($hash, 0, 4));
        return (int) ($unpacked[1] ?? 1);
    }

    private function loadStep(int $attemptId, int $quizId, bool $shuffleOptions, int $quizPk): void
    {
        $questionId = $this->questionIds[$this->step - 1] ?? null;
        if (! $questionId) {
            throw ValidationException::withMessages(['step' => 'Soal tidak ditemukan.']);
        }

        $question = Question::query()
            ->where('quiz_id', $quizId)
            ->where('id', $questionId)
            ->first();

        if (! $question) {
            throw ValidationException::withMessages(['step' => 'Soal tidak ditemukan.']);
        }

        $this->currentQuestionId = (int) $question->id;
        $this->currentQuestionText = (string) $question->question_text;
        $this->currentQuestionImagePath = is_string($question->question_image_path) ? $question->question_image_path : null;
        $this->currentQuestionType = (string) $question->question_type;
        $this->currentDifficultyLevel = (string) ($question->difficulty_level ?? QuestionDifficulty::DEFAULT);
        $this->currentAnswerIsCorrect = null;
        $this->currentCorrectOptionId = null;
        $this->currentAnswerLocked = false;
        $this->lockedSelectedOptionId = null;

        $answer = AttemptAnswer::query()
            ->where('quiz_attempt_id', $attemptId)
            ->where('question_id', $question->id)
            ->first();

        $this->suppressInstantFeedbackLock = true;
        $this->selectedOptionId = $answer?->selected_option_id ? (int) $answer->selected_option_id : null;
        $this->suppressInstantFeedbackLock = false;
        $this->shortAnswerText = (string) ($answer?->answer_text ?? '');

        $this->currentOptions = [];
        if ($question->question_type === 'multiple_choice') {
            $options = QuestionOption::query()
                ->where('question_id', $question->id)
                ->whereNull('deleted_at')
                ->orderBy('sort_order')
                ->get(['id', 'option_text', 'option_image_path', 'sort_order', 'is_correct']);

            $items = $options->map(fn ($o) => [
                'id' => (int) $o->id,
                'text' => (string) ($o->option_text ?? ''),
                'image_path' => is_string($o->option_image_path) ? $o->option_image_path : null,
                'sort_order' => (int) $o->sort_order,
                'is_correct' => (bool) $o->is_correct,
            ])->all();

            if ($shuffleOptions && count($items) > 1) {
                $seed = $this->seedForQuestion($attemptId, $quizPk, (int) $question->id);
                $items = DeterministicShuffle::shuffle($items, $seed);
            }

            $labels = ['A', 'B', 'C', 'D', 'E'];
            foreach (array_values($items) as $idx => $it) {
                $this->currentOptions[] = [
                    'id' => (int) $it['id'],
                    'label' => $labels[$idx] ?? '',
                    'text' => (string) $it['text'],
                    'image_path' => $it['image_path'],
                    'is_correct' => (bool) $it['is_correct'],
                ];
            }

            $this->currentCorrectOptionId = collect($this->currentOptions)
                ->firstWhere('is_correct', true)['id'] ?? null;

            if ($this->instantFeedbackEnabled && $answer && $answer->selected_option_id) {
                $this->currentAnswerLocked = true;
                $this->currentAnswerIsCorrect = (bool) $answer->is_correct;
                $this->lockedSelectedOptionId = (int) $answer->selected_option_id;
            }
        }
    }

    private function lockCurrentMultipleChoiceAnswer(): void
    {
        $this->expireAttemptIfMultiUseLinkExpired();
        if ($this->state === 'expired') {
            return;
        }

        if (! $this->instantFeedbackEnabled || $this->currentQuestionType !== 'multiple_choice' || ! $this->currentQuestionId) {
            return;
        }

        if (! $this->selectedOptionId || $this->currentAnswerLocked) {
            return;
        }

        $attempt = QuizAttempt::query()->find($this->attemptId);
        if (! $attempt || (int) $attempt->quiz_link_id !== (int) $this->linkId) {
            return;
        }

        if ($attempt->status !== 'in_progress') {
            return;
        }

        $this->secondsRemaining = $this->calculateSecondsRemaining($attempt);
        if ($this->secondsRemaining <= 0) {
            return;
        }

        $selectedOptionId = (int) $this->selectedOptionId;
        $correctOptionId = (int) ($this->currentCorrectOptionId ?? 0);
        $isCorrect = $selectedOptionId !== 0 && $selectedOptionId === $correctOptionId;

        AttemptAnswer::updateOrCreate(
            ['quiz_attempt_id' => $attempt->id, 'question_id' => $this->currentQuestionId],
            [
                'selected_option_id' => $selectedOptionId,
                'answer_text' => null,
                'is_correct' => $isCorrect,
                'answered_at' => now(),
                'skipped_at' => null,
            ],
        );

        $this->currentAnswerLocked = true;
        $this->currentAnswerIsCorrect = $isCorrect;
        $this->lockedSelectedOptionId = $selectedOptionId;
        $this->refreshProgress();
    }

    private function finalizeAutoIfNeeded(): void
    {
        $this->finalize('auto_submitted');
        $this->state = 'expired_view';
        $this->redirect('/quiz/'.$this->token.'/done', navigate: false);
    }

    /**
     * @param  'submitted'|'auto_submitted'  $resultStatus
     */
    private function finalize(string $resultStatus): void
    {
        if ($this->attemptId <= 0) {
            return;
        }

        $gradeService = new GradeService();
        $resultId = null;

        DB::transaction(function () use ($resultStatus, $gradeService, &$resultId): void {
            $attempt = QuizAttempt::query()
                ->where('id', $this->attemptId)
                ->lockForUpdate()
                ->first();

            $link = QuizLink::query()
                ->where('token', $this->token)
                ->lockForUpdate()
                ->first();

            if (! $attempt || ! $link) {
                return;
            }

            if (in_array($attempt->status, ['submitted', 'auto_submitted'], true)) {
                return;
            }

            if ($link->usage_type !== 'multi' && in_array($link->status, ['submitted', 'expired'], true)) {
                return;
            }

            $secondsRemaining = $this->calculateSecondsRemaining($attempt);
            $isExpired = $secondsRemaining <= 0;

            $finalAttemptStatus = $isExpired ? 'auto_submitted' : $resultStatus;
            $finalLinkStatus = $isExpired ? 'expired' : 'submitted';

            $now = now();

            $attempt->status = $finalAttemptStatus;
            $attempt->submitted_at = $now;
            $attempt->save();

            if ($link->usage_type !== 'multi') {
                $link->status = $finalLinkStatus;
                $link->submitted_at = $now;
                $link->expired_at = $now;
                $link->save();
            }

            $questionIds = DB::table('questions')
                ->where('quiz_id', $attempt->quiz_id)
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $total = count($questionIds);

            $answers = AttemptAnswer::query()
                ->where('quiz_attempt_id', $attempt->id)
                ->whereIn('question_id', $questionIds)
                ->get(['id', 'question_id', 'selected_option_id', 'answer_text'])
                ->keyBy('question_id');

            $questions = Question::query()
                ->whereIn('id', $questionIds)
                ->get(['id', 'question_type'])
                ->keyBy('id');

            $correct = 0;
            $wrong = 0;
            $unanswered = 0;

            $isCorrectByQuestionId = [];

            foreach ($questionIds as $qid) {
                $question = $questions->get($qid);
                if (! $question) {
                    continue;
                }

                $answer = $answers->get($qid);
                if (! $answer) {
                    $unanswered++;
                    $isCorrectByQuestionId[$qid] = false;
                    continue;
                }

                if ($question->question_type === 'multiple_choice') {
                    if (! $answer->selected_option_id) {
                        $unanswered++;
                        $isCorrectByQuestionId[$qid] = false;
                        continue;
                    }

                    $isCorrect = (bool) DB::table('question_options')
                        ->where('id', $answer->selected_option_id)
                        ->where('question_id', $qid)
                        ->whereNull('deleted_at')
                        ->value('is_correct');

                    $isCorrectByQuestionId[$qid] = $isCorrect;

                    if ($isCorrect) {
                        $correct++;
                    } else {
                        $wrong++;
                    }

                    continue;
                }

                $text = trim((string) ($answer->answer_text ?? ''));
                if ($text === '') {
                    $unanswered++;
                    $isCorrectByQuestionId[$qid] = false;
                    continue;
                }

                $normalized = $this->normalizeShortAnswer($text);
                $exists = DB::table('short_answer_keys')
                    ->where('question_id', $qid)
                    ->where('normalized_answer_text', $normalized)
                    ->exists();

                $isCorrectByQuestionId[$qid] = $exists;

                if ($exists) {
                    $correct++;
                } else {
                    $wrong++;
                }
            }

            if ($total <= 0) {
                $score = 0.0;
            } else {
                $score = ($correct / $total) * 100.0;
            }

            $score2 = (float) number_format($score, 2, '.', '');
            $grade = $gradeService->fromScorePercentage($score2);

            $result = QuizResult::updateOrCreate(
                ['quiz_attempt_id' => $attempt->id],
                [
                    'quiz_id' => $attempt->quiz_id,
                    'total_questions' => $total,
                    'correct_answers' => $correct,
                    'wrong_answers' => $wrong,
                    'unanswered_answers' => $unanswered,
                    'score_percentage' => $score2,
                    'grade_letter' => $grade['grade_letter'],
                    'grade_label' => $grade['grade_label'],
                    'result_status' => $finalAttemptStatus === 'auto_submitted' ? 'auto_submitted' : 'submitted',
                    'calculated_at' => $now,
                ],
            );

            $resultId = (int) $result->id;

            foreach ($answers as $qid => $a) {
                AttemptAnswer::query()
                    ->where('id', $a->id)
                    ->update(['is_correct' => (bool) ($isCorrectByQuestionId[$qid] ?? false)]);
            }
        });

        if ($resultId) {
            try {
                app(ResultPdfService::class)->generateForResultId($resultId);
            } catch (\Throwable $e) {
                Log::error('result pdf generation threw', [
                    'quiz_result_id' => $resultId,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $attempt = QuizAttempt::query()
                    ->with('quizLink:id,usage_type')
                    ->where('id', $this->attemptId)
                    ->first(['id', 'quiz_link_id']);

                $isMulti = (bool) ($attempt?->quizLink && (string) $attempt->quizLink->usage_type === 'multi');
                if (! $isMulti) {
                    app(DiscordResultWebhookService::class)->sendForResultId($resultId);
                }
            } catch (\Throwable $e) {
                Log::error('discord webhook send failed', [
                    'quiz_result_id' => $resultId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function isMultiUseExpired(QuizLink $link): bool
    {
        if (! $link->expires_at) {
            return false;
        }

        return CarbonImmutable::now()->gte(CarbonImmutable::parse($link->expires_at));
    }

    private function getAttemptSessionKey(QuizLink $link): string
    {
        return self::SESSION_ATTEMPT_KEY_PREFIX.$link->token;
    }

    private function getAttemptFromSession(QuizLink $link): ?QuizAttempt
    {
        $attemptId = session()->get($this->getAttemptSessionKey($link));
        if (! is_int($attemptId) || $attemptId <= 0) {
            return null;
        }

        return QuizAttempt::query()
            ->where('id', $attemptId)
            ->where('quiz_link_id', $link->id)
            ->first();
    }

    private function expireAttemptIfMultiUseLinkExpired(): void
    {
        if ($this->linkId <= 0 || $this->attemptId <= 0) {
            return;
        }

        $link = QuizLink::query()->find($this->linkId);
        if (! $link || $link->usage_type !== 'multi') {
            return;
        }

        if (! $this->isMultiUseExpired($link)) {
            return;
        }

        DB::transaction(function () use ($link): void {
            $now = now();

            $attempt = QuizAttempt::query()
                ->where('id', $this->attemptId)
                ->lockForUpdate()
                ->first();

            $linkLocked = QuizLink::query()
                ->where('id', $link->id)
                ->lockForUpdate()
                ->first();

            if (! $attempt || ! $linkLocked) {
                return;
            }

            if ((int) $attempt->quiz_link_id !== (int) $linkLocked->id) {
                return;
            }

            if (in_array((string) $attempt->status, ['submitted', 'auto_submitted', 'expired'], true)) {
                return;
            }

            $attempt->status = 'expired';
            $attempt->submitted_at = $now;
            $attempt->save();

            if ($linkLocked->status !== 'expired') {
                $linkLocked->status = 'expired';
                $linkLocked->expired_at = $linkLocked->expired_at ?? $now;
                $linkLocked->save();
            }
        });

        $this->state = 'expired';
    }

    private function normalizeShortAnswer(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return $text;
    }

    public function render()
    {
        return view('livewire.participant.quiz-work');
    }
}
