<?php

namespace App\Services;

use App\Support\QuestionDifficulty;
use App\Services\Xlsx\SimpleXlsxReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class QuizQuestionImportParser
{
    public function __construct(
        private readonly SimpleXlsxReader $reader = new SimpleXlsxReader(),
    ) {
    }

    /**
     * @return array<int, array{
     *   question_text:string,
     *   question_type:'multiple_choice'|'short_answer',
     *   difficulty_level:string,
     *   options:array<int, array{option_key:string, option_text:string, is_correct:bool, sort_order:int}>,
     *   short_answers:array<int, string>
     * }>
     */
    public function parse(UploadedFile $file): array
    {
        $rows = $this->reader->readFirstWorksheetRows($file->getRealPath());
        if ($rows === []) {
            throw ValidationException::withMessages([
                'importFile' => 'File import tidak valid.',
            ]);
        }

        $headerRow = $rows[0];
        $headerMap = $this->validateAndMapHeaders($headerRow);

        $dataRows = array_slice($rows, 1);
        if ($dataRows === []) {
            throw ValidationException::withMessages([
                'importFile' => 'File import tidak valid.',
            ]);
        }

        $parsed = [];
        $errors = [];

        foreach ($dataRows as $index => $row) {
            $excelRowNumber = $index + 2;

            $values = $this->pluckRowValues($row, $headerMap);
            if ($this->isRowEmpty($values)) {
                continue;
            }

            try {
                $parsed[] = $this->parseAndValidateRow($values, $excelRowNumber);
            } catch (ValidationException $e) {
                $errors[$excelRowNumber] = array_values($e->errors());
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'importFile' => $this->formatRowErrors($errors),
            ]);
        }

        if ($parsed === []) {
            throw ValidationException::withMessages([
                'importFile' => 'Tidak ada baris valid untuk diimport.',
            ]);
        }

        return $parsed;
    }

    /**
     * @param  array<string, string>  $headerRow
     * @return array<string, string> headerKey => columnLetter
     */
    private function validateAndMapHeaders(array $headerRow): array
    {
        $required = [
            'soal',
            'jenis_jawaban',
            'opsi_a',
            'opsi_b',
            'opsi_c',
            'opsi_d',
            'opsi_e',
            'jawaban_benar',
            'short_answer',
        ];
        $optional = [
            'tingkat_kesulitan',
        ];

        $normalized = [];
        foreach ($headerRow as $col => $value) {
            $key = $this->normalizeHeader((string) $value);
            if ($key !== '') {
                $normalized[$key] = $col;
            }
        }

        $map = [];
        foreach ($required as $key) {
            if (! isset($normalized[$key])) {
                throw ValidationException::withMessages([
                    'importFile' => 'Header file import tidak sesuai template.',
                ]);
            }
            $map[$key] = $normalized[$key];
        }

        foreach ($optional as $key) {
            if (isset($normalized[$key])) {
                $map[$key] = $normalized[$key];
            }
        }

        return $map;
    }

    private function normalizeHeader(string $text): string
    {
        $text = trim(mb_strtolower($text));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return match ($text) {
            'soal' => 'soal',
            'jenis jawaban' => 'jenis_jawaban',
            'opsi a' => 'opsi_a',
            'opsi b' => 'opsi_b',
            'opsi c' => 'opsi_c',
            'opsi d' => 'opsi_d',
            'opsi e' => 'opsi_e',
            'jawaban benar' => 'jawaban_benar',
            'short answer' => 'short_answer',
            'tingkat kesulitan' => 'tingkat_kesulitan',
            default => '',
        };
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, string>  $headerMap
     * @return array<string, string>
     */
    private function pluckRowValues(array $row, array $headerMap): array
    {
        $out = [];
        foreach ($headerMap as $key => $col) {
            $out[$key] = (string) ($row[$col] ?? '');
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function isRowEmpty(array $values): bool
    {
        foreach ($values as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param  array<string, string>  $values
     * @return array{
     *   question_text:string,
     *   question_type:'multiple_choice'|'short_answer',
     *   difficulty_level:string,
     *   options:array<int, array{option_key:string, option_text:string, is_correct:bool, sort_order:int}>,
     *   short_answers:array<int, string>
     * }
     */
    private function parseAndValidateRow(array $values, int $rowNumber): array
    {
        $questionText = trim($values['soal'] ?? '');
        if ($questionText === '') {
            throw ValidationException::withMessages([
                'soal' => "Baris $rowNumber: Soal wajib diisi.",
            ]);
        }

        $typeRaw = (string) ($values['jenis_jawaban'] ?? '');
        $type = $this->normalizeType($typeRaw);
        if (! in_array($type, ['multiple_choice', 'short_answer'], true)) {
            throw ValidationException::withMessages([
                'jenis_jawaban' => "Baris $rowNumber: Jenis Jawaban tidak valid.",
            ]);
        }

        $difficulty = $this->parseDifficulty($values['tingkat_kesulitan'] ?? '', $rowNumber);

        if ($type === 'multiple_choice') {
            $shortAnswer = trim((string) ($values['short_answer'] ?? ''));
            if ($shortAnswer !== '') {
                throw ValidationException::withMessages([
                    'short_answer' => "Baris $rowNumber: Short Answer harus kosong untuk Multiple Choice.",
                ]);
            }

            $answerKey = strtoupper(trim((string) ($values['jawaban_benar'] ?? '')));
            if (! in_array($answerKey, ['A', 'B', 'C', 'D', 'E'], true)) {
                throw ValidationException::withMessages([
                    'jawaban_benar' => "Baris $rowNumber: Jawaban Benar harus salah satu dari A/B/C/D/E.",
                ]);
            }

            $optionsMap = [
                'A' => trim((string) ($values['opsi_a'] ?? '')),
                'B' => trim((string) ($values['opsi_b'] ?? '')),
                'C' => trim((string) ($values['opsi_c'] ?? '')),
                'D' => trim((string) ($values['opsi_d'] ?? '')),
                'E' => trim((string) ($values['opsi_e'] ?? '')),
            ];

            $options = [];
            $sortOrder = 1;
            $correctCount = 0;

            foreach (['A', 'B', 'C', 'D', 'E'] as $k) {
                $text = $optionsMap[$k];
                if ($text === '') {
                    continue;
                }

                $isCorrect = ($k === $answerKey);
                if ($isCorrect) {
                    $correctCount++;
                }

                $options[] = [
                    'option_key' => $k,
                    'option_text' => $text,
                    'is_correct' => $isCorrect,
                    'sort_order' => $sortOrder,
                ];

                $sortOrder++;
            }

            if (count($options) < 2) {
                throw ValidationException::withMessages([
                    'options' => "Baris $rowNumber: Minimal 2 opsi harus diisi.",
                ]);
            }

            $correctOptionExists = false;
            foreach ($options as $opt) {
                if ($opt['option_key'] === $answerKey) {
                    $correctOptionExists = true;
                }
            }

            if (! $correctOptionExists) {
                throw ValidationException::withMessages([
                    'jawaban_benar' => "Baris $rowNumber: Jawaban Benar menunjuk opsi yang kosong.",
                ]);
            }

            if ($correctCount !== 1) {
                throw ValidationException::withMessages([
                    'jawaban_benar' => "Baris $rowNumber: Tepat 1 jawaban benar wajib diisi.",
                ]);
            }

            return [
                'question_text' => $questionText,
                'question_type' => 'multiple_choice',
                'difficulty_level' => $difficulty,
                'options' => $options,
                'short_answers' => [],
            ];
        }

        $jawabanBenar = trim((string) ($values['jawaban_benar'] ?? ''));
        if ($jawabanBenar !== '') {
            throw ValidationException::withMessages([
                'jawaban_benar' => "Baris $rowNumber: Jawaban Benar harus kosong untuk Short Answer.",
            ]);
        }

        $shortRaw = (string) ($values['short_answer'] ?? '');
        $shortAnswers = $this->parseShortAnswers($shortRaw);
        if (count($shortAnswers) < 1) {
            throw ValidationException::withMessages([
                'short_answer' => "Baris $rowNumber: Short Answer wajib diisi.",
            ]);
        }

        return [
            'question_text' => $questionText,
            'question_type' => 'short_answer',
            'difficulty_level' => $difficulty,
            'options' => [],
            'short_answers' => $shortAnswers,
        ];
    }

    private function normalizeType(string $raw): string
    {
        $raw = trim(mb_strtolower($raw));
        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;
        $raw = str_replace(' ', '_', $raw);
        return $raw;
    }

    private function parseDifficulty(string $raw, int $rowNumber): string
    {
        $rawNormalized = mb_strtolower(trim($raw));
        $rawNormalized = preg_replace('/\s+/', ' ', $rawNormalized) ?? $rawNormalized;

        $difficulty = QuestionDifficulty::normalize($raw);
        if ($difficulty === null || $rawNormalized === 'sangat_sulit') {
            throw ValidationException::withMessages([
                'tingkat_kesulitan' => "Baris $rowNumber: Tingkat Kesulitan harus mudah, sedang, sulit, atau sangat sulit.",
            ]);
        }

        return $difficulty;
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
     * @param  array<int, array<int, string>>  $errors
     */
    private function formatRowErrors(array $errors): string
    {
        $lines = [];
        ksort($errors);
        foreach ($errors as $rowNumber => $messages) {
            foreach ($messages as $msgs) {
                if (is_array($msgs)) {
                    foreach ($msgs as $m) {
                        $lines[] = (string) $m;
                    }
                } else {
                    $lines[] = (string) $msgs;
                }
            }
        }

        return implode("\n", array_values(array_unique($lines)));
    }
}
