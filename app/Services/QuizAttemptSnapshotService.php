<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Support\DeterministicShuffle;
use App\Support\QuestionDifficulty;

class QuizAttemptSnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function ensureForAttempt(QuizAttempt $attempt): array
    {
        $snapshot = $this->validSnapshot($attempt->quiz_snapshot);
        if ($snapshot !== null) {
            return $snapshot;
        }

        $quiz = Quiz::query()
            ->with([
                'questions' => fn ($q) => $q->orderBy('order_number'),
                'questions.options' => fn ($q) => $q->orderBy('sort_order'),
                'questions.shortAnswerKeys' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->find($attempt->quiz_id);

        if (! $quiz) {
            return ['quiz' => [], 'questions' => []];
        }

        $snapshot = $this->buildForQuiz($quiz);
        $attempt->forceFill(['quiz_snapshot' => $snapshot])->save();

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForQuiz(Quiz $quiz): array
    {
        return [
            'quiz' => [
                'id' => (int) $quiz->id,
                'title' => (string) $quiz->title,
                'description' => $quiz->description,
                'duration_minutes' => (int) $quiz->duration_minutes,
                'shuffle_questions' => (bool) $quiz->shuffle_questions,
                'shuffle_options' => (bool) $quiz->shuffle_options,
                'instant_feedback_enabled' => (bool) $quiz->instant_feedback_enabled,
                'difficulty_levels_enabled' => (bool) $quiz->difficulty_levels_enabled,
                'show_result_to_participant' => (bool) $quiz->show_result_to_participant,
            ],
            'questions' => $quiz->questions
                ->filter(fn ($question) => (bool) $question->is_active)
                ->sortBy('order_number')
                ->values()
                ->map(fn ($question) => [
                    'id' => (int) $question->id,
                    'question_type' => (string) $question->question_type,
                    'question_text' => (string) ($question->question_text ?? ''),
                    'question_image_path' => is_string($question->question_image_path) ? $question->question_image_path : null,
                    'difficulty_level' => (string) ($question->difficulty_level ?? QuestionDifficulty::DEFAULT),
                    'order_number' => (int) $question->order_number,
                    'options' => $question->options
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn ($option) => [
                            'id' => (int) $option->id,
                            'option_key' => (string) $option->option_key,
                            'option_text' => (string) ($option->option_text ?? ''),
                            'option_image_path' => is_string($option->option_image_path) ? $option->option_image_path : null,
                            'is_correct' => (bool) $option->is_correct,
                            'sort_order' => (int) $option->sort_order,
                        ])
                        ->all(),
                    'short_answers' => $question->shortAnswerKeys
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn ($key) => [
                            'answer_text' => (string) $key->answer_text,
                            'normalized_answer_text' => (string) $key->normalized_answer_text,
                            'sort_order' => (int) $key->sort_order,
                        ])
                        ->all(),
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<int, int>
     */
    public function orderedQuestionIds(?array $snapshot, int $attemptId): array
    {
        $snapshot = $this->validSnapshot($snapshot);
        if ($snapshot === null) {
            return [];
        }

        $rows = collect($snapshot['questions'] ?? [])
            ->sortBy('order_number')
            ->map(fn ($question) => [
                'id' => (int) ($question['id'] ?? 0),
                'difficulty_level' => (string) (($question['difficulty_level'] ?? null) ?: QuestionDifficulty::DEFAULT),
            ])
            ->filter(fn ($question) => (int) $question['id'] > 0)
            ->values()
            ->all();

        if ($rows === []) {
            return [];
        }

        $quiz = $snapshot['quiz'] ?? [];
        $quizId = (int) ($quiz['id'] ?? 0);
        $shuffleQuestions = (bool) ($quiz['shuffle_questions'] ?? false);
        $difficultyLevelsEnabled = (bool) ($quiz['difficulty_levels_enabled'] ?? false);

        if (! $difficultyLevelsEnabled) {
            $ids = array_map(fn (array $row) => (int) $row['id'], $rows);
            if (! $shuffleQuestions || count($ids) <= 1) {
                return $ids;
            }

            return DeterministicShuffle::shuffle($ids, $this->seedFromAttempt($attemptId, $quizId));
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
                    $this->seedForDifficulty($attemptId, $quizId, $difficultyLevel)
                );
            }

            array_push($ids, ...$bucketIds);
        }

        return $ids !== [] ? $ids : array_map(fn (array $row) => (int) $row['id'], $rows);
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    public function question(?array $snapshot, int $questionId): ?array
    {
        $snapshot = $this->validSnapshot($snapshot);
        if ($snapshot === null) {
            return null;
        }

        foreach (($snapshot['questions'] ?? []) as $question) {
            if ((int) ($question['id'] ?? 0) === $questionId) {
                return is_array($question) ? $question : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<int, array<string, mixed>>
     */
    public function questionsById(?array $snapshot): array
    {
        $snapshot = $this->validSnapshot($snapshot);
        if ($snapshot === null) {
            return [];
        }

        $out = [];
        foreach (($snapshot['questions'] ?? []) as $question) {
            if (! is_array($question)) {
                continue;
            }

            $id = (int) ($question['id'] ?? 0);
            if ($id > 0) {
                $out[$id] = $question;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    public function validSnapshot(?array $snapshot): ?array
    {
        if (! is_array($snapshot)) {
            return null;
        }

        if (! isset($snapshot['quiz'], $snapshot['questions']) || ! is_array($snapshot['questions'])) {
            return null;
        }

        return $snapshot;
    }

    private function seedFromAttempt(int $attemptId, int $quizId): int
    {
        $hash = hash('sha256', 'attempt:'.$attemptId.':quiz:'.$quizId, true);
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
