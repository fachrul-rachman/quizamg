<?php

use App\Services\QuizQuestionImportParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it('defaults missing difficulty column to mudah while importing xlsx questions', function () {
    $file = uploadedQuizImportFile([
        ['Soal', 'Jenis Jawaban', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Jawaban Benar', 'Short Answer'],
        ['2 + 2 = ?', 'multiple_choice', '3', '4', '', '', '', 'B', ''],
    ]);

    $rows = app(QuizQuestionImportParser::class)->parse($file);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['difficulty_level'])->toBe('mudah');
});

it('rejects invalid difficulty values in xlsx import', function () {
    $file = uploadedQuizImportFile([
        ['Soal', 'Jenis Jawaban', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Jawaban Benar', 'Short Answer', 'Tingkat Kesulitan'],
        ['2 + 2 = ?', 'multiple_choice', '3', '4', '', '', '', 'B', '', 'expert'],
    ]);

    try {
        app(QuizQuestionImportParser::class)->parse($file);
        $this->fail('Expected validation exception was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors()['importFile'][0])
            ->toContain('Tingkat Kesulitan harus mudah, sedang, sulit, atau sangat sulit');
    }
});

it('accepts sangat sulit as the highest xlsx difficulty value', function () {
    $file = uploadedQuizImportFile([
        ['Soal', 'Jenis Jawaban', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Jawaban Benar', 'Short Answer', 'Tingkat Kesulitan'],
        ['2 + 2 = ?', 'multiple_choice', '3', '4', '', '', '', 'B', '', 'sangat sulit'],
    ]);

    $rows = app(QuizQuestionImportParser::class)->parse($file);

    expect($rows[0]['difficulty_level'])->toBe('sangat_sulit');
});

function uploadedQuizImportFile(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $columnIndex => $value) {
            $cell = Coordinate::stringFromColumnIndex($columnIndex + 1).($rowIndex + 1);
            $sheet->setCellValue($cell, $value);
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'quiz-import-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile(
        $path,
        'import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}
