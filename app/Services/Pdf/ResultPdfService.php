<?php

namespace App\Services\Pdf;

use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizLink;
use App\Models\QuizResult;
use App\Models\ResultPdf;
use App\Models\ShortAnswerKey;
use App\Services\GoogleDrive\GoogleDriveFolderService;
use App\Services\GoogleDrive\GoogleDriveUploadService;
use App\Support\DeterministicShuffle;
use App\Support\QuestionDifficulty;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ResultPdfService
{
    public function generateForResultId(int $quizResultId): void
    {
        $result = QuizResult::query()->find($quizResultId);
        if (! $result) {
            return;
        }

        $this->generateForResult($result);
    }

    public function generateForResult(QuizResult $result): void
    {
        $existing = ResultPdf::query()->where('quiz_result_id', $result->id)->first();
        if ($existing) {
            $this->uploadToGoogleDriveIfEnabled($existing);
            return;
        }

        $attempt = QuizAttempt::query()->find($result->quiz_attempt_id);
        $quiz = Quiz::query()->find($result->quiz_id);

        if (! $attempt || ! $quiz) {
            return;
        }

        try {
            $questionIds = $this->orderedQuestionIds($quiz, $attempt);
            $rows = $this->buildRows($attempt, $questionIds, (bool) $quiz->shuffle_options);

            $printedAt = now();
            $score = number_format((float) $result->score_percentage, 2, '.', '');
            $fileNameBase = $this->sanitizeFileName($quiz->title.' - '.$attempt->participant_name.' - '.$printedAt->format('Y-m-d H-i').' - Score '.$score.' - Grade '.$result->grade_letter);
            $fileName = $fileNameBase.'.pdf';
            $relativePath = 'results/'.$result->id.'/'.$fileName;

            $html = view('pdf.result', [
                'quiz' => $quiz,
                'attempt' => $attempt,
                'result' => $result,
                'rows' => $rows,
                'printedAt' => $printedAt,
            ])->render();

            $pdfBinary = $this->renderPdf($html);

            Storage::disk('local')->put($relativePath, $pdfBinary);

            $pdf = ResultPdf::create([
                'quiz_result_id' => $result->id,
                'file_name' => $fileName,
                'local_path' => $relativePath,
                'google_drive_file_id' => null,
                'google_drive_url' => null,
                'generated_at' => $printedAt,
                'uploaded_at' => null,
            ]);

            $this->uploadToGoogleDriveIfEnabled($pdf);
        } catch (\Throwable $e) {
            Log::error('result pdf generation failed', [
                'quiz_result_id' => $result->id,
                'error' => $e->getMessage(),
            ]);
        }
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

            $seed = $this->seedFromAttempt((int) $attempt->id, (int) $quiz->id);
            return DeterministicShuffle::shuffle($ids, $seed);
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
     * @return array<int, array{
     *   no:int,
     *   question_text:string,
     *   participant_answer:?string,
     *   correct_answer:?string,
     *   status:'correct'|'wrong'|'unanswered'
     * }>
     */
    private function buildRows(QuizAttempt $attempt, array $questionIds, bool $shuffleOptions): array
    {
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->get(['id', 'question_text', 'difficulty_level', 'question_type'])
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
            ->get(['id', 'question_id', 'option_text', 'is_correct', 'sort_order'])
            ->groupBy('question_id');

        $keysByQuestion = ShortAnswerKey::query()
            ->whereIn('question_id', $questionIds)
            ->orderBy('sort_order')
            ->get(['question_id', 'answer_text'])
            ->groupBy('question_id');

        $rows = [];
        foreach (array_values($questionIds) as $idx => $qid) {
            $q = $questions->get($qid);
            if (! $q) {
                continue;
            }

            $a = $answers->get($qid);

            $participantAnswer = null;
            $correctAnswer = null;
            $status = 'unanswered';

            if ($q->question_type === 'multiple_choice') {
                $opts = $optionsByQuestion->get($qid, collect());
                $items = $opts->map(fn ($o) => [
                    'id' => (int) $o->id,
                    'text' => (string) ($o->option_text ?? ''),
                    'is_correct' => (bool) $o->is_correct,
                    'sort_order' => (int) $o->sort_order,
                ])->all();

                if ($shuffleOptions && count($items) > 1) {
                    $seed = $this->seedForQuestion((int) $attempt->id, (int) $attempt->quiz_id, $qid);
                    $items = DeterministicShuffle::shuffle($items, $seed);
                }

                $selectedId = $a?->selected_option_id ? (int) $a->selected_option_id : null;
                if ($selectedId) {
                    $selectedText = null;
                    foreach ($items as $it) {
                        if ((int) $it['id'] === $selectedId) {
                            $selectedText = (string) $it['text'];
                            break;
                        }
                    }
                    $participantAnswer = $selectedText;
                }

                $correctText = null;
                foreach ($items as $it) {
                    if (! empty($it['is_correct'])) {
                        $correctText = (string) $it['text'];
                        break;
                    }
                }
                $correctAnswer = $correctText;

                if (! $participantAnswer) {
                    $status = 'unanswered';
                } else {
                    $status = ($a?->is_correct ?? false) ? 'correct' : 'wrong';
                }
            } else {
                $participantAnswer = is_string($a?->answer_text) ? trim($a->answer_text) : null;

                $keys = $keysByQuestion->get($qid, collect())->pluck('answer_text')->all();
                $correctAnswer = $keys !== [] ? implode(' | ', $keys) : null;

                if (! $participantAnswer) {
                    $status = 'unanswered';
                } else {
                    $status = ($a?->is_correct ?? false) ? 'correct' : 'wrong';
                }
            }

            $rows[] = [
                'no' => $idx + 1,
                'difficulty_level' => (string) ($q->difficulty_level ?? QuestionDifficulty::DEFAULT),
                'difficulty_label' => QuestionDifficulty::label((string) ($q->difficulty_level ?? QuestionDifficulty::DEFAULT)),
                'question_text' => (string) $q->question_text,
                'participant_answer' => $participantAnswer,
                'correct_answer' => $correctAnswer,
                'status' => $status,
            ];
        }

        return $rows;
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $out = $dompdf->output();
        if (! is_string($out) || $out === '') {
            throw new RuntimeException('PDF output empty.');
        }

        return $out;
    }

    private function uploadToGoogleDriveIfEnabled(ResultPdf $pdf): void
    {
        if (! filter_var(env('GOOGLE_DRIVE_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (is_string($pdf->google_drive_file_id) && $pdf->google_drive_file_id !== '') {
            $this->deleteLocalPdfIfPresent($pdf);
            return;
        }

        $folderId = (string) env('GOOGLE_DRIVE_FOLDER_ID', '');
        if ($folderId === '') {
            return;
        }

        $uploadFolderId = $this->resolveUploadFolderId($pdf, $folderId);
        if ($uploadFolderId === '') {
            return;
        }

        $relativePath = is_string($pdf->local_path) ? $pdf->local_path : '';
        if ($relativePath === '' || ! Storage::disk('local')->exists($relativePath)) {
            return;
        }

        try {
            $absolutePath = Storage::disk('local')->path($relativePath);
            $fileName = (string) $pdf->file_name;

            $res = app(GoogleDriveUploadService::class)->uploadPdf($absolutePath, $fileName, $uploadFolderId);
            if (! $res) {
                return;
            }

            $pdf->google_drive_file_id = (string) $res['file_id'];
            $pdf->google_drive_url = (string) $res['google_drive_url'];
            $pdf->uploaded_at = now();
            $pdf->save();

            $this->deleteLocalPdfIfPresent($pdf);
        } catch (\Throwable $e) {
            Log::error('google drive upload failed', [
                'quiz_result_id' => $pdf->quiz_result_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function deleteLocalPdfIfPresent(ResultPdf $pdf): void
    {
        $relativePath = is_string($pdf->local_path) ? $pdf->local_path : '';
        if ($relativePath === '') {
            return;
        }

        try {
            if (Storage::disk('local')->exists($relativePath)) {
                Storage::disk('local')->delete($relativePath);
            }

            $pdf->local_path = null;
            $pdf->save();
        } catch (\Throwable $e) {
            Log::warning('local pdf delete failed', [
                'quiz_result_id' => $pdf->quiz_result_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[\\\\\\/:"*?<>|]+/', '-', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = trim($name);
        $name = Str::limit($name, 120, '');
        return $name === '' ? 'hasil' : $name;
    }

    private function resolveUploadFolderId(ResultPdf $pdf, string $rootFolderId): string
    {
        $result = QuizResult::query()
            ->with([
                'quiz:id,title',
                'attempt:id,quiz_link_id',
                'attempt.quizLink:id,usage_type,google_drive_folder_id,google_drive_folder_url',
            ])
            ->find((int) $pdf->quiz_result_id);

        if (! $result) {
            return $rootFolderId;
        }

        $attempt = $result->attempt;
        $quiz = $result->quiz;
        if (! $attempt || ! $quiz) {
            return $rootFolderId;
        }

        $link = $attempt->quizLink;
        if (! $link || (string) $link->usage_type !== 'multi') {
            return $rootFolderId;
        }

        if (is_string($link->google_drive_folder_id) && $link->google_drive_folder_id !== '') {
            return $link->google_drive_folder_id;
        }

        return $this->createAndStoreLinkFolderId($link, $quiz->title, $rootFolderId) ?? '';
    }

    private function createAndStoreLinkFolderId(QuizLink $link, string $quizTitle, string $rootFolderId): ?string
    {
        $name = $this->sanitizeFileName($quizTitle.' - Link #'.$link->id);
        $res = app(GoogleDriveFolderService::class)->createFolder($name, $rootFolderId);
        if (! $res) {
            return null;
        }

        $link->google_drive_folder_id = (string) $res['folder_id'];
        $link->google_drive_folder_url = (string) $res['google_drive_url'];
        $link->save();

        return (string) $res['folder_id'];
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
}
