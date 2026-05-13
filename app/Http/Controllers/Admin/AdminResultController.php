<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizResult;
use App\Models\ResultPdf;
use App\Models\ShortAnswerKey;
use App\Services\Export\ResultExportXlsxService;
use App\Support\DeterministicShuffle;
use App\Support\ParticipantAppliedForNormalizer;
use App\Support\QuestionDifficulty;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminResultController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $search = trim((string) $request->query('search', ''));
        $quizId = (string) $request->query('quiz_id', '');
        $status = (string) $request->query('status', 'all');
        $jabatan = ParticipantAppliedForNormalizer::normalize(trim((string) $request->query('jabatan', '')));
        [$startAt, $endAt] = $this->resolveSubmittedAtRange($request);

        $results = QuizResult::query()
            ->select('quiz_results.*')
            ->with([
                'quiz:id,title',
                'attempt:id,quiz_id,participant_name,participant_applied_for,status,submitted_at',
            ])
            ->when(! $isSuperAdmin && $user, function ($query) use ($user) {
                $query->whereHas('quiz', fn ($q) => $q->where('created_by', (int) $user->id));
            })
            ->when($search !== '', function ($query) use ($search) {
                $needle = mb_strtolower($search);

                $query->where(function ($inner) use ($needle) {
                    $inner->whereHas('quiz', function ($quizQuery) use ($needle) {
                        $quizQuery->whereRaw('LOWER(title) LIKE ?', ['%'.$needle.'%']);
                    })->orWhereHas('attempt', function ($attemptQuery) use ($needle) {
                        $attemptQuery
                            ->whereRaw('LOWER(participant_name) LIKE ?', ['%'.$needle.'%'])
                            ->orWhereRaw('LOWER(participant_applied_for) LIKE ?', ['%'.$needle.'%']);
                    });
                });
            })
            ->when($quizId !== '', fn ($query) => $query->where('quiz_results.quiz_id', (int) $quizId))
            ->when($status !== 'all', fn ($query) => $query->where('quiz_results.result_status', $status))
            ->when($jabatan !== '', function ($query) use ($jabatan) {
                $needle = mb_strtolower($jabatan);
                $query->whereHas('attempt', fn ($q) => $q->whereRaw('LOWER(participant_applied_for) = ?', [$needle]));
            })
            ->when($startAt || $endAt, function ($query) use ($startAt, $endAt) {
                $query->whereHas('attempt', function ($attemptQuery) use ($startAt, $endAt) {
                    if ($startAt) {
                        $attemptQuery->where('submitted_at', '>=', $startAt);
                    }
                    if ($endAt) {
                        $attemptQuery->where('submitted_at', '<=', $endAt);
                    }
                });
            })
            ->orderByDesc('quiz_results.calculated_at')
            ->orderByDesc('quiz_results.id')
            ->paginate(10)
            ->withQueryString();

        $pdfByResultId = ResultPdf::query()
            ->whereIn('quiz_result_id', $results->getCollection()->pluck('id'))
            ->get(['quiz_result_id', 'google_drive_url', 'uploaded_at'])
            ->keyBy('quiz_result_id');

        $rows = $results->getCollection()->map(function (QuizResult $result) use ($pdfByResultId) {
            $pdf = $pdfByResultId->get($result->id);

            return [
                'result' => $result,
                'pdf' => $pdf,
            ];
        });

        $quizzes = Quiz::query()
            ->when(! $isSuperAdmin && $user, fn ($q) => $q->where('created_by', (int) $user->id))
            ->orderBy('title')
            ->get(['id', 'title']);

        $jabatanOptions = $this->jabatanOptionsForUser($isSuperAdmin ? null : (int) ($user?->id ?? 0));

        return view('admin.results.index', [
            'results' => $results,
            'rows' => $rows,
            'quizzes' => $quizzes,
            'search' => $search,
            'quizId' => $quizId,
            'status' => $status,
            'jabatan' => $jabatan,
            'jabatanOptions' => $jabatanOptions,
            'dateFrom' => $request->query('date_from', ''),
            'dateTo' => $request->query('date_to', ''),
            'rangePreset' => (string) $request->query('range', ''),
        ]);
    }

    public function show(QuizResult $quizResult): View
    {
        $user = request()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $quizResult->load([
            'quiz:id,title,description,duration_minutes,shuffle_questions,shuffle_options,difficulty_levels_enabled,created_by',
            'attempt:id,quiz_id,participant_name,participant_applied_for,started_at,submitted_at,time_limit_minutes,status',
        ]);

        $attempt = $quizResult->attempt;
        $quiz = $quizResult->quiz;
        abort_unless($attempt && $quiz, 404);
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        $pdf = ResultPdf::query()
            ->where('quiz_result_id', $quizResult->id)
            ->first(['quiz_result_id', 'file_name', 'local_path', 'google_drive_url', 'generated_at', 'uploaded_at']);

        $questionIds = $this->orderedQuestionIds($quiz, $attempt);
        $questionRows = $this->buildQuestionRows($attempt, $questionIds, (bool) $quiz->shuffle_options);

        return view('admin.results.show', [
            'result' => $quizResult,
            'quiz' => $quiz,
            'attempt' => $attempt,
            'pdf' => $pdf,
            'questionRows' => $questionRows,
        ]);
    }

    public function export(Request $request, ResultExportXlsxService $exportService): BinaryFileResponse
    {
        $user = $request->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');

        $quizId = $request->query('quiz_id', '');
        $status = (string) $request->query('status', 'all');
        $jabatan = trim((string) $request->query('jabatan', ''));
        [$startAt, $endAt] = $this->resolveSubmittedAtRange($request);

        $payload = $exportService->exportToTempFile([
            'quiz_id' => ctype_digit((string) $quizId) ? (int) $quizId : null,
            'jabatan' => $jabatan !== '' ? $jabatan : null,
            'status' => $status,
            'start_at' => $startAt,
            'end_at' => $endAt,
        ], $isSuperAdmin ? null : (int) ($user?->id ?? 0), $isSuperAdmin);

        return response()->download($payload['path'], $payload['download_name'])->deleteFileAfterSend(true);
    }

    /**
     * @return array{0:?CarbonImmutable,1:?CarbonImmutable}
     */
    private function resolveSubmittedAtRange(Request $request): array
    {
        $range = (string) $request->query('range', '');
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $tz = 'Asia/Jakarta';
        $now = CarbonImmutable::now($tz);

        if ($range === 'week') {
            return [$now->subDays(7)->startOfDay(), $now->endOfDay()];
        }

        if ($range === 'month') {
            return [$now->subDays(30)->startOfDay(), $now->endOfDay()];
        }

        if ($range === 'year') {
            return [$now->subDays(365)->startOfDay(), $now->endOfDay()];
        }

        $start = null;
        $end = null;

        if ($dateFrom !== '') {
            try {
                $start = CarbonImmutable::parse($dateFrom, $tz)->startOfDay();
            } catch (\Throwable) {
                $start = null;
            }
        }

        if ($dateTo !== '') {
            try {
                $end = CarbonImmutable::parse($dateTo, $tz)->endOfDay();
            } catch (\Throwable) {
                $end = null;
            }
        }

        return [$start, $end];
    }

    /**
     * @return array<int, string>
     */
    private function jabatanOptionsForUser(?int $creatorUserId): array
    {
        $query = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->whereNotNull('quiz_attempts.participant_applied_for')
            ->where('quiz_attempts.participant_applied_for', '!=', '')
            ->selectRaw('DISTINCT quiz_attempts.participant_applied_for as jabatan')
            ->orderBy('jabatan');

        if ($creatorUserId !== null && $creatorUserId > 0) {
            $query->where('quizzes.created_by', $creatorUserId);
        }

        return $query
            ->pluck('jabatan')
            ->map(fn ($v) => ParticipantAppliedForNormalizer::normalize((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique(fn ($v) => mb_strtolower((string) $v))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function orderedQuestionIds(Quiz $quiz, QuizAttempt $attempt): array
    {
        $rows = Question::query()
            ->where('quiz_id', $quiz->id)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get(['id', 'difficulty_level'])
            ->map(fn ($question) => [
                'id' => (int) $question->id,
                'difficulty_level' => (string) ($question->difficulty_level ?? QuestionDifficulty::DEFAULT),
            ])
            ->all();

        if ($rows === []) {
            return [];
        }

        if (! $quiz->difficulty_levels_enabled) {
            $ids = array_map(fn (array $row) => (int) $row['id'], $rows);
            if (! $quiz->shuffle_questions || count($ids) <= 1) {
                return $ids;
            }

            return DeterministicShuffle::shuffle($ids, $this->seedFromAttempt((int) $attempt->id, (int) $quiz->id));
        }

        $ids = [];
        foreach (QuestionDifficulty::LEVELS as $difficultyLevel) {
            $bucket = array_values(array_filter(
                $rows,
                fn (array $row) => (($row['difficulty_level'] ?: QuestionDifficulty::DEFAULT) === $difficultyLevel)
            ));

            $bucketIds = array_map(fn (array $row) => (int) $row['id'], $bucket);
            if ($quiz->shuffle_questions && count($bucketIds) > 1) {
                $bucketIds = DeterministicShuffle::shuffle(
                    $bucketIds,
                    $this->seedForDifficulty((int) $attempt->id, (int) $quiz->id, $difficultyLevel)
                );
            }

            array_push($ids, ...$bucketIds);
        }

        if ($ids !== []) {
            return $ids;
        }

        return array_map(fn (array $row) => (int) $row['id'], $rows);
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestionRows(QuizAttempt $attempt, array $questionIds, bool $shuffleOptions): array
    {
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->get(['id', 'question_text', 'question_image_path', 'difficulty_level', 'question_type'])
            ->keyBy('id');

        $answers = AttemptAnswer::query()
            ->where('quiz_attempt_id', $attempt->id)
            ->whereIn('question_id', $questionIds)
            ->get(['question_id', 'selected_option_id', 'answer_text', 'is_correct'])
            ->keyBy('question_id');

        $optionsByQuestion = QuestionOption::query()
            ->whereIn('question_id', $questionIds)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get(['id', 'question_id', 'option_key', 'option_text', 'option_image_path', 'is_correct', 'sort_order'])
            ->groupBy('question_id');

        $keysByQuestion = ShortAnswerKey::query()
            ->whereIn('question_id', $questionIds)
            ->orderBy('sort_order')
            ->get(['question_id', 'answer_text'])
            ->groupBy('question_id');

        $rows = [];

        foreach (array_values($questionIds) as $index => $questionId) {
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            $answer = $answers->get($questionId);
            $status = 'unanswered';
            $participantAnswer = null;
            $correctAnswer = null;
            $options = [];
            $acceptedAnswers = [];

            if ($question->question_type === 'multiple_choice') {
                $optionItems = $optionsByQuestion->get($questionId, collect())
                    ->map(fn ($option) => [
                        'id' => (int) $option->id,
                        'option_key' => (string) $option->option_key,
                        'option_text' => (string) ($option->option_text ?? ''),
                        'option_image_url' => $this->publicStorageUrl($option->option_image_path),
                        'is_correct' => (bool) $option->is_correct,
                        'sort_order' => (int) $option->sort_order,
                    ])
                    ->values()
                    ->all();

                if ($shuffleOptions && count($optionItems) > 1) {
                    $optionItems = DeterministicShuffle::shuffle(
                        $optionItems,
                        $this->seedForQuestion((int) $attempt->id, (int) $attempt->quiz_id, $questionId)
                    );
                }

                $selectedId = $answer?->selected_option_id ? (int) $answer->selected_option_id : null;

                foreach ($optionItems as $optionItem) {
                    $isSelected = $selectedId !== null && $selectedId === (int) $optionItem['id'];
                    $options[] = $optionItem + ['is_selected' => $isSelected];

                    if ($isSelected) {
                        $participantAnswer = $this->formatOptionAnswer($optionItem);
                    }

                    if (! empty($optionItem['is_correct'])) {
                        $correctAnswer = $this->formatOptionAnswer($optionItem);
                    }
                }

                $status = $participantAnswer === null
                    ? 'unanswered'
                    : (($answer?->is_correct ?? false) ? 'correct' : 'wrong');
            } else {
                $participantAnswer = is_string($answer?->answer_text) ? trim($answer->answer_text) : null;
                $acceptedAnswers = $keysByQuestion->get($questionId, collect())
                    ->pluck('answer_text')
                    ->map(fn ($text) => (string) $text)
                    ->values()
                    ->all();
                $correctAnswer = $acceptedAnswers !== [] ? implode(' | ', $acceptedAnswers) : null;
                $status = $participantAnswer === null || $participantAnswer === ''
                    ? 'unanswered'
                    : (($answer?->is_correct ?? false) ? 'correct' : 'wrong');
            }

            $rows[] = [
                'no' => $index + 1,
                'question_type' => (string) $question->question_type,
                'difficulty_level' => (string) ($question->difficulty_level ?? QuestionDifficulty::DEFAULT),
                'difficulty_label' => QuestionDifficulty::label((string) ($question->difficulty_level ?? QuestionDifficulty::DEFAULT)),
                'question_text' => (string) ($question->question_text ?? ''),
                'question_image_url' => $this->publicStorageUrl($question->question_image_path),
                'participant_answer' => $participantAnswer,
                'correct_answer' => $correctAnswer,
                'accepted_answers' => $acceptedAnswers,
                'status' => $status,
                'options' => $options,
            ];
        }

        return $rows;
    }

    private function formatOptionAnswer(array $option): string
    {
        $parts = [];

        if (($option['option_key'] ?? '') !== '') {
            $parts[] = $option['option_key'].'.';
        }

        if (($option['option_text'] ?? '') !== '') {
            $parts[] = $option['option_text'];
        }

        if (($option['option_image_url'] ?? null) !== null) {
            $parts[] = '[gambar]';
        }

        return implode(' ', $parts);
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function seedFromAttempt(int $attemptId, int $quizId): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizId, true);
        $unpacked = unpack('N', substr($hash, 0, 4));

        return (int) ($unpacked[1] ?? 1);
    }

    private function seedForQuestion(int $attemptId, int $quizId, int $questionId): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizId.':q:'.$questionId, true);
        $unpacked = unpack('N', substr($hash, 0, 4));

        return (int) ($unpacked[1] ?? 1);
    }

    private function seedForDifficulty(int $attemptId, int $quizId, string $difficultyLevel): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizId.':difficulty:'.$difficultyLevel, true);
        $unpacked = unpack('N', substr($hash, 0, 4));

        return (int) ($unpacked[1] ?? 1);
    }
}
