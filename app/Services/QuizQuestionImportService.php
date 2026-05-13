<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\ShortAnswerKey;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizQuestionImportService
{
    public function __construct(
        private readonly QuizQuestionImportParser $parser = new QuizQuestionImportParser(),
    ) {
    }

    /**
     * @return array{inserted:int}
     */
    public function import(Quiz $quiz, UploadedFile $file, int $userId): array
    {
        try {
            $parsed = $this->parser->parse($file);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([
                'file' => $e->errors()['importFile'][0] ?? 'File import tidak valid.',
            ]);
        }

        $inserted = 0;

        DB::transaction(function () use ($quiz, $parsed, $userId, &$inserted): void {
            $baseOrder = (int) (Question::query()
                ->where('quiz_id', $quiz->id)
                ->whereNull('deleted_at')
                ->max('order_number') ?? 0);

            foreach (array_values($parsed) as $offset => $q) {
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question_type' => $q['question_type'],
                    'question_text' => $q['question_text'],
                    'question_image_path' => null,
                    'difficulty_level' => $q['difficulty_level'],
                    'order_number' => $baseOrder + $offset + 1,
                    'is_active' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                if ($q['question_type'] === 'multiple_choice') {
                    foreach ($q['options'] as $opt) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_key' => $opt['option_key'],
                            'option_text' => $opt['option_text'],
                            'option_image_path' => null,
                            'is_correct' => $opt['is_correct'],
                            'sort_order' => $opt['sort_order'],
                        ]);
                    }
                } else {
                    foreach ($q['short_answers'] as $idx => $ans) {
                        ShortAnswerKey::create([
                            'question_id' => $question->id,
                            'answer_text' => $ans,
                            'normalized_answer_text' => $this->normalizeShortAnswer($ans),
                            'sort_order' => $idx + 1,
                        ]);
                    }
                }

                $inserted++;
            }
        });

        return ['inserted' => $inserted];
    }

    private function normalizeShortAnswer(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return $text;
    }
}
