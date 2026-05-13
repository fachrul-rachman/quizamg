<?php

namespace App\Livewire\Admin;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\ShortAnswerKey;
use App\Services\QuizQuestionImportParser;
use App\Support\QuestionDifficulty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class QuizBuilder extends Component
{
    use WithFileUploads;

    public ?int $quizId = null;

    public string $title = '';
    public ?string $description = null;
    public int $durationMinutes = 1;
    public bool $shuffleQuestions = false;
    public bool $shuffleOptions = false;
    public bool $instantFeedbackEnabled = false;
    public bool $difficultyLevelsEnabled = false;
    public bool $isActive = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $questions = [];

    public $importFile;

    public function mount(?int $quizId = null): void
    {
        $this->quizId = $quizId;

        if ($quizId === null) {
            $this->questions = [
                $this->blankQuestion(1),
            ];

            return;
        }

        $quiz = Quiz::query()
            ->with(['questions' => fn ($q) => $q->orderBy('order_number'), 'questions.options' => fn ($q) => $q->orderBy('sort_order'), 'questions.shortAnswerKeys' => fn ($q) => $q->orderBy('sort_order')])
            ->findOrFail($quizId);

        $user = auth()->user();
        $isSuperAdmin = (($user?->role ?? null) === 'super_admin');
        if (! $isSuperAdmin && (int) $quiz->created_by !== (int) ($user?->id ?? 0)) {
            abort(404);
        }

        $this->title = $quiz->title;
        $this->description = $quiz->description;
        $this->durationMinutes = (int) $quiz->duration_minutes;
        $this->shuffleQuestions = (bool) $quiz->shuffle_questions;
        $this->shuffleOptions = (bool) $quiz->shuffle_options;
        $this->instantFeedbackEnabled = (bool) $quiz->instant_feedback_enabled;
        $this->difficultyLevelsEnabled = (bool) $quiz->difficulty_levels_enabled;
        $this->isActive = (bool) $quiz->is_active;

        $this->questions = $quiz->questions->map(function (Question $question) {
            if ($question->question_type === 'short_answer') {
                $keys = $question->shortAnswerKeys->pluck('answer_text')->all();
                $keyString = implode('|', $keys);
            } else {
                $keyString = '';
            }

            $options = $question->options->map(function (QuestionOption $opt) {
                return [
                    'id' => $opt->id,
                    'option_text' => (string) ($opt->option_text ?? ''),
                    'option_image_path' => $opt->option_image_path,
                    'option_image_upload' => null,
                    'remove_option_image' => false,
                    'is_correct' => (bool) $opt->is_correct,
                ];
            })->all();

            if (count($options) < 2) {
                $options = [
                    $this->blankOption(),
                    $this->blankOption(),
                ];
            }

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_image_path' => $question->question_image_path,
                'question_image_upload' => null,
                'remove_question_image' => false,
                'difficulty_level' => $question->difficulty_level ?: QuestionDifficulty::DEFAULT,
                'question_type' => $question->question_type,
                'is_active' => (bool) $question->is_active,
                'options' => $options,
                'short_answers' => $keyString,
            ];
        })->all();

        if ($this->questions === []) {
            $this->questions = [
                $this->blankQuestion(1),
            ];
        }
    }

    public function addQuestion(): void
    {
        $this->questions[] = $this->blankQuestion(count($this->questions) + 1);
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);
        $this->questions = array_values($this->questions);
    }

    public function removeQuestionImage(int $questionIndex): void
    {
        if (! isset($this->questions[$questionIndex])) {
            return;
        }

        $this->questions[$questionIndex]['question_image_upload'] = null;
        $this->questions[$questionIndex]['remove_question_image'] = true;
    }

    public function addOption(int $questionIndex): void
    {
        $options = $this->questions[$questionIndex]['options'] ?? [];
        if (count($options) >= 5) {
            return;
        }

        $options[] = $this->blankOption();
        $this->questions[$questionIndex]['options'] = $options;
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        $options = $this->questions[$questionIndex]['options'] ?? [];
        unset($options[$optionIndex]);
        $options = array_values($options);

        if (count($options) < 2) {
            $options[] = $this->blankOption();
        }

        $this->questions[$questionIndex]['options'] = $options;
    }

    public function removeOptionImage(int $questionIndex, int $optionIndex): void
    {
        if (! isset($this->questions[$questionIndex]['options'][$optionIndex])) {
            return;
        }

        $this->questions[$questionIndex]['options'][$optionIndex]['option_image_upload'] = null;
        $this->questions[$questionIndex]['options'][$optionIndex]['remove_option_image'] = true;
    }

    public function markCorrect(int $questionIndex, int $optionIndex): void
    {
        $options = $this->questions[$questionIndex]['options'] ?? [];
        foreach ($options as $idx => $opt) {
            $options[$idx]['is_correct'] = ($idx === $optionIndex);
        }
        $this->questions[$questionIndex]['options'] = $options;
    }

    public function save(): void
    {
        try {
            $this->validateBase();
            $this->validateImages();
            $this->validateQuestions();
        } catch (ValidationException $e) {
            $firstErrorKey = array_key_first($e->errors()) ?? null;

            $this->dispatch(
                'qb:validation-failed',
                firstErrorKey: $firstErrorKey,
                message: 'Ada yang perlu diperbaiki. Kamu akan diarahkan ke bagian yang bermasalah.',
            );

            throw $e;
        }

        DB::transaction(function (): void {
            $quiz = $this->quizId
                ? Quiz::query()->findOrFail($this->quizId)
                : new Quiz();

            $quiz->fill([
                'title' => $this->title,
                'description' => $this->description,
                'duration_minutes' => $this->durationMinutes,
                'shuffle_questions' => $this->shuffleQuestions,
                'shuffle_options' => $this->shuffleOptions,
                'instant_feedback_enabled' => $this->instantFeedbackEnabled,
                'difficulty_levels_enabled' => $this->difficultyLevelsEnabled,
                'is_active' => $this->isActive,
            ]);

            if (! $quiz->exists) {
                $quiz->created_by = auth()->id();
            }

            $quiz->updated_by = auth()->id();
            $quiz->save();

            $keepQuestionIds = [];

            foreach (array_values($this->questions) as $pos => $q) {
                $question = null;
                if (! empty($q['id'])) {
                    $question = Question::query()
                        ->where('quiz_id', $quiz->id)
                        ->where('id', (int) $q['id'])
                        ->first();
                }

                if (! $question) {
                    $question = new Question();
                    $question->quiz_id = $quiz->id;
                    $question->created_by = auth()->id();
                }

                $question->fill([
                    'question_type' => $q['question_type'],
                    'question_text' => $this->nullableTrim($q['question_text'] ?? null),
                    'difficulty_level' => $this->normalizedDifficulty($q['difficulty_level'] ?? null),
                    'order_number' => $pos + 1,
                    'is_active' => (bool) ($q['is_active'] ?? true),
                ]);
                $question->question_image_path = $this->storeQuestionImage($question, $q);
                $question->updated_by = auth()->id();
                $question->save();

                $keepQuestionIds[] = $question->id;

                if ($q['question_type'] === 'multiple_choice') {
                    $this->syncOptions($question, $q['options']);
                    $this->deleteShortAnswerKeys($question);
                } else {
                    $this->deleteOptions($question);
                    $this->syncShortAnswers($question, (string) ($q['short_answers'] ?? ''));
                }
            }

            $this->deleteRemovedQuestions($quiz->id, $keepQuestionIds);
        });

        session()->flash('success', 'Quiz berhasil disimpan.');
        $this->redirect('/admin/quizzes', navigate: false);
    }

    public function importFromXlsx(QuizQuestionImportParser $parser): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx'],
        ], [
            'importFile.required' => 'File import wajib diisi.',
            'importFile.mimes' => 'File import tidak valid.',
        ]);

        $parsed = $parser->parse($this->importFile);

        foreach ($parsed as $q) {
            if ($q['question_type'] === 'multiple_choice') {
                $options = array_map(fn ($opt) => [
                    'id' => null,
                    'option_text' => $opt['option_text'],
                    'option_image_path' => null,
                    'option_image_upload' => null,
                    'remove_option_image' => false,
                    'is_correct' => (bool) $opt['is_correct'],
                ], $q['options']);

                if (count($options) < 2) {
                    $options = [
                        $this->blankOption(),
                        $this->blankOption(),
                    ];
                }

                $this->questions[] = [
                    'id' => null,
                    'question_text' => $q['question_text'],
                    'question_image_path' => null,
                    'question_image_upload' => null,
                    'remove_question_image' => false,
                    'difficulty_level' => $q['difficulty_level'] ?? QuestionDifficulty::DEFAULT,
                    'question_type' => 'multiple_choice',
                    'is_active' => true,
                    'options' => $options,
                    'short_answers' => '',
                ];
            } else {
                $this->questions[] = [
                    'id' => null,
                    'question_text' => $q['question_text'],
                    'question_image_path' => null,
                    'question_image_upload' => null,
                    'remove_question_image' => false,
                    'difficulty_level' => $q['difficulty_level'] ?? QuestionDifficulty::DEFAULT,
                    'question_type' => 'short_answer',
                    'is_active' => true,
                    'options' => [
                        $this->blankOption(),
                        $this->blankOption(),
                    ],
                    'short_answers' => implode('|', $q['short_answers']),
                ];
            }
        }

        $this->importFile = null;
        session()->flash('success', 'Import soal berhasil dilakukan.');
    }

    private function validateBase(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'shuffleQuestions' => ['boolean'],
            'shuffleOptions' => ['boolean'],
            'instantFeedbackEnabled' => ['boolean'],
            'difficultyLevelsEnabled' => ['boolean'],
            'isActive' => ['boolean'],
        ], [], [
            'title' => 'Nama quiz',
            'description' => 'Deskripsi',
            'durationMinutes' => 'Durasi',
            'shuffleQuestions' => 'Shuffle Soal',
            'shuffleOptions' => 'Shuffle Opsi',
            'instantFeedbackEnabled' => 'Tampilkan Jawaban Benar',
            'difficultyLevelsEnabled' => 'Kesulitan Bertingkat',
            'isActive' => 'Status Aktif',
        ]);
    }

    private function validateImages(): void
    {
        $this->validate([
            'questions.*.question_image_upload' => ['nullable', 'image', 'max:2048'],
            'questions.*.options.*.option_image_upload' => ['nullable', 'image', 'max:2048'],
        ], [
            'questions.*.question_image_upload.image' => 'Gambar soal harus berupa file gambar.',
            'questions.*.question_image_upload.max' => 'Gambar soal maksimal 2MB.',
            'questions.*.options.*.option_image_upload.image' => 'Gambar opsi harus berupa file gambar.',
            'questions.*.options.*.option_image_upload.max' => 'Gambar opsi maksimal 2MB.',
        ]);
    }

    private function validateQuestions(): void
    {
        if (count($this->questions) < 1) {
            throw ValidationException::withMessages([
                'questions' => 'Minimal 1 soal harus diisi.',
            ]);
        }

        foreach (array_values($this->questions) as $qi => $q) {
            if (! $this->questionHasContent($q)) {
                throw ValidationException::withMessages([
                    "questions.$qi.question_text" => 'Soal wajib punya teks atau gambar.',
                ]);
            }

            $difficulty = QuestionDifficulty::normalize($q['difficulty_level'] ?? null);
            if ($difficulty === null || ! QuestionDifficulty::isValid($difficulty)) {
                throw ValidationException::withMessages([
                    "questions.$qi.difficulty_level" => 'Tingkat kesulitan tidak valid.',
                ]);
            }

            $type = (string) ($q['question_type'] ?? '');
            if (! in_array($type, ['multiple_choice', 'short_answer'], true)) {
                throw ValidationException::withMessages([
                    "questions.$qi.question_type" => 'Jenis jawaban tidak valid.',
                ]);
            }

            if ($this->instantFeedbackEnabled && $type !== 'multiple_choice') {
                throw ValidationException::withMessages([
                    "questions.$qi.question_type" => 'Mode tampilkan jawaban benar hanya bisa dipakai untuk quiz multiple choice.',
                ]);
            }

            if ($type === 'multiple_choice') {
                $options = $q['options'] ?? [];
                if (! is_array($options) || count($options) < 2) {
                    throw ValidationException::withMessages([
                        "questions.$qi.options" => 'Minimal 2 opsi harus diisi.',
                    ]);
                }

                $correctCount = 0;
                foreach (array_values($options) as $oi => $opt) {
                    if (! $this->optionHasContent($opt)) {
                        throw ValidationException::withMessages([
                            "questions.$qi.options.$oi.option_text" => 'Opsi wajib punya teks atau gambar.',
                        ]);
                    }
                    if (! empty($opt['is_correct'])) {
                        $correctCount++;
                    }
                }

                if ($correctCount !== 1) {
                    throw ValidationException::withMessages([
                        "questions.$qi.options" => 'Tepat 1 opsi harus ditandai benar.',
                    ]);
                }
            } else {
                $raw = (string) ($q['short_answers'] ?? '');
                $keys = $this->parseShortAnswers($raw);
                if (count($keys) < 1) {
                    throw ValidationException::withMessages([
                        "questions.$qi.short_answers" => 'Jawaban benar wajib diisi.',
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        $keys = ['A', 'B', 'C', 'D', 'E'];
        $keepOptionIds = [];

        foreach (array_values($options) as $idx => $opt) {
            $option = null;
            if (! empty($opt['id'])) {
                $option = QuestionOption::query()
                    ->where('question_id', $question->id)
                    ->where('id', (int) $opt['id'])
                    ->first();
            }

            if (! $option) {
                $option = new QuestionOption();
                $option->question_id = $question->id;
            }

            $option->fill([
                'option_key' => $keys[$idx] ?? 'E',
                'option_text' => $this->nullableTrim($opt['option_text'] ?? null),
                'is_correct' => (bool) ($opt['is_correct'] ?? false),
                'sort_order' => $idx + 1,
            ]);
            $option->option_image_path = $this->storeOptionImage($option, $opt);
            $option->save();

            $keepOptionIds[] = $option->id;
        }

        $this->deleteRemovedOptions($question->id, $keepOptionIds);
    }

    private function syncShortAnswers(Question $question, string $raw): void
    {
        $keys = $this->parseShortAnswers($raw);

        ShortAnswerKey::query()->where('question_id', $question->id)->delete();

        foreach ($keys as $idx => $answer) {
            ShortAnswerKey::create([
                'question_id' => $question->id,
                'answer_text' => $answer,
                'normalized_answer_text' => $this->normalizeShortAnswer($answer),
                'sort_order' => $idx + 1,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseShortAnswers(string $raw): array
    {
        $parts = array_map('trim', explode('|', $raw));
        $parts = array_values(array_filter($parts, fn ($v) => $v !== ''));

        $seen = [];
        $out = [];
        foreach ($parts as $p) {
            $norm = $this->normalizeShortAnswer($p);
            if ($norm === '' || isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $out[] = $p;
        }

        return $out;
    }

    private function normalizeShortAnswer(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankQuestion(int $order): array
    {
        return [
            'id' => null,
            'question_text' => '',
            'question_image_path' => null,
            'question_image_upload' => null,
            'remove_question_image' => false,
            'difficulty_level' => QuestionDifficulty::DEFAULT,
            'question_type' => 'multiple_choice',
            'is_active' => true,
            'options' => [
                $this->blankOption(),
                $this->blankOption(),
            ],
            'short_answers' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankOption(): array
    {
        return [
            'id' => null,
            'option_text' => '',
            'option_image_path' => null,
            'option_image_upload' => null,
            'remove_option_image' => false,
            'is_correct' => false,
        ];
    }

    private function questionHasContent(array $question): bool
    {
        if ($this->nullableTrim($question['question_text'] ?? null) !== null) {
            return true;
        }

        if (! empty($question['question_image_upload'])) {
            return true;
        }

        return is_string($question['question_image_path'] ?? null)
            && $question['question_image_path'] !== ''
            && empty($question['remove_question_image']);
    }

    private function optionHasContent(array $option): bool
    {
        if ($this->nullableTrim($option['option_text'] ?? null) !== null) {
            return true;
        }

        if (! empty($option['option_image_upload'])) {
            return true;
        }

        return is_string($option['option_image_path'] ?? null)
            && $option['option_image_path'] !== ''
            && empty($option['remove_option_image']);
    }

    private function nullableTrim(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function normalizedDifficulty(mixed $value): string
    {
        $difficulty = QuestionDifficulty::normalize($value);

        return $difficulty ?? QuestionDifficulty::DEFAULT;
    }

    private function storeQuestionImage(Question $question, array $payload): ?string
    {
        $currentPath = is_string($question->question_image_path) && $question->question_image_path !== ''
            ? $question->question_image_path
            : null;

        if (! empty($payload['question_image_upload'])) {
            $this->deletePublicFile($currentPath);

            return $payload['question_image_upload']->store('quiz-images/questions', 'public');
        }

        if (! empty($payload['remove_question_image'])) {
            $this->deletePublicFile($currentPath);

            return null;
        }

        return $currentPath;
    }

    private function storeOptionImage(QuestionOption $option, array $payload): ?string
    {
        $currentPath = is_string($option->option_image_path) && $option->option_image_path !== ''
            ? $option->option_image_path
            : null;

        if (! empty($payload['option_image_upload'])) {
            $this->deletePublicFile($currentPath);

            return $payload['option_image_upload']->store('quiz-images/options', 'public');
        }

        if (! empty($payload['remove_option_image'])) {
            $this->deletePublicFile($currentPath);

            return null;
        }

        return $currentPath;
    }

    private function deleteRemovedQuestions(int $quizId, array $keepQuestionIds): void
    {
        $query = Question::query()
            ->where('quiz_id', $quizId);

        if ($keepQuestionIds !== []) {
            $query->whereNotIn('id', $keepQuestionIds);
        }

        $questions = $query->get();

        foreach ($questions as $question) {
            $this->deletePublicFile($question->question_image_path);
            $this->deleteOptions($question);
            ShortAnswerKey::query()->where('question_id', $question->id)->delete();
            $question->delete();
        }
    }

    private function deleteOptions(Question $question): void
    {
        $options = QuestionOption::query()
            ->where('question_id', $question->id)
            ->get();

        foreach ($options as $option) {
            $this->deletePublicFile($option->option_image_path);
            $option->delete();
        }
    }

    private function deleteRemovedOptions(int $questionId, array $keepOptionIds): void
    {
        $query = QuestionOption::query()
            ->where('question_id', $questionId);

        if ($keepOptionIds !== []) {
            $query->whereNotIn('id', $keepOptionIds);
        }

        $options = $query->get();

        foreach ($options as $option) {
            $this->deletePublicFile($option->option_image_path);
            $option->delete();
        }
    }

    private function deleteShortAnswerKeys(Question $question): void
    {
        ShortAnswerKey::query()->where('question_id', $question->id)->delete();
    }

    private function deletePublicFile(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function render()
    {
        return view('livewire.admin.quiz-builder');
    }
}
