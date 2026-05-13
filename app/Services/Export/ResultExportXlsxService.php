<?php

namespace App\Services\Export;

use Carbon\CarbonImmutable;
use App\Support\ParticipantAppliedForNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ResultExportXlsxService
{
    private const TEMPLATE_PATH = 'prep/hasil_ujian_v2.xlsx';
    private const DATA_START_ROW = 6;
    private const TZ = 'Asia/Jakarta';

    /**
     * @param  array{
     *   quiz_id?:int|null,
     *   jabatan?:string|null,
     *   status?:string|null,
     *   start_at?:CarbonImmutable|null,
     *   end_at?:CarbonImmutable|null
     * }  $filters
     */
    public function exportToTempFile(array $filters, ?int $creatorUserId, bool $isSuperAdmin): array
    {
        $templateAbsolutePath = base_path(self::TEMPLATE_PATH);
        if (! File::exists($templateAbsolutePath)) {
            throw new \RuntimeException('Template Excel tidak ditemukan.');
        }

        $rows = $this->loadRows($filters, $creatorUserId, $isSuperAdmin);

        $spreadsheet = IOFactory::load($templateAbsolutePath);
        $sheet = $spreadsheet->getActiveSheet();

        $evenStyle = $sheet->getStyle('A6:I6')->exportArray();
        $oddStyle = $sheet->getStyle('A7:I7')->exportArray();
        $rowHeight = $sheet->getRowDimension(6)->getRowHeight();

        $highestRow = (int) $sheet->getHighestDataRow();
        if ($highestRow >= self::DATA_START_ROW) {
            $sheet->removeRow(self::DATA_START_ROW, $highestRow - self::DATA_START_ROW + 1);
        }

        if (count($rows) > 0) {
            $sheet->insertNewRowBefore(self::DATA_START_ROW, count($rows));
        }

        foreach (array_values($rows) as $i => $row) {
            $excelRow = self::DATA_START_ROW + $i;
            $style = ($i % 2 === 0) ? $evenStyle : $oddStyle;
            $sheet->getStyle("A{$excelRow}:I{$excelRow}")->applyFromArray($style);
            $sheet->getRowDimension($excelRow)->setRowHeight($rowHeight);

            $sheet->setCellValueExplicit("A{$excelRow}", $row['nama'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$excelRow}", $row['jabatan'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$excelRow}", $row['tanggal_submit'], DataType::TYPE_STRING);
            $sheet->setCellValue("D{$excelRow}", $row['jumlah_benar']);
            $sheet->setCellValue("E{$excelRow}", $row['jumlah_salah']);
            $sheet->setCellValue("F{$excelRow}", $row['jumlah_kosong']);
            $sheet->setCellValue("G{$excelRow}", $row['total_soal']);
            $sheet->setCellValueExplicit("H{$excelRow}", $row['grade'], DataType::TYPE_STRING);

            $this->applyGradeStyle($sheet->getStyle("H{$excelRow}"), $row['grade']);

            if ($row['link_drive'] !== '') {
                $sheet->setCellValueExplicit("I{$excelRow}", $row['link_drive'], DataType::TYPE_STRING);
                $sheet->getCell("I{$excelRow}")->setHyperlink(new Hyperlink($row['link_drive']));
                $sheet->getStyle("I{$excelRow}")->applyFromArray([
                    'font' => [
                        'color' => ['argb' => Color::COLOR_BLUE],
                        'underline' => true,
                    ],
                ]);
            } else {
                $sheet->setCellValueExplicit("I{$excelRow}", '', DataType::TYPE_STRING);
            }
        }

        $this->applyHeaderMeta($sheet, $filters);

        $tempDir = storage_path('app/tmp');
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        $fileName = 'export_hasil_test_'.CarbonImmutable::now(self::TZ)->format('Ymd_His').'.xlsx';
        $tempPath = $tempDir.DIRECTORY_SEPARATOR.$fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return [
            'path' => $tempPath,
            'download_name' => $this->downloadFileName($filters),
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, array{
     *   nama:string,
     *   jabatan:string,
     *   tanggal_submit:string,
     *   jumlah_benar:int,
     *   jumlah_salah:int,
     *   jumlah_kosong:int,
     *   total_soal:int,
     *   grade:string,
     *   link_drive:string
     * }>
     */
    private function loadRows(array $filters, ?int $creatorUserId, bool $isSuperAdmin): array
    {
        $query = DB::table('quiz_results')
            ->join('quiz_attempts', 'quiz_attempts.id', '=', 'quiz_results.quiz_attempt_id')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_results.quiz_id')
            ->leftJoin('result_pdfs', 'result_pdfs.quiz_result_id', '=', 'quiz_results.id')
            ->select([
                'quiz_attempts.participant_name',
                'quiz_attempts.participant_applied_for',
                'quiz_attempts.submitted_at',
                'quiz_results.correct_answers',
                'quiz_results.wrong_answers',
                'quiz_results.unanswered_answers',
                'quiz_results.total_questions',
                'quiz_results.grade_letter',
                'result_pdfs.google_drive_url',
                'quizzes.created_by as quiz_created_by',
            ])
            ->whereNotNull('quiz_attempts.submitted_at');

        if (! $isSuperAdmin && $creatorUserId !== null && $creatorUserId > 0) {
            $query->where('quizzes.created_by', $creatorUserId);
        }

        $quizId = $filters['quiz_id'] ?? null;
        if (is_int($quizId) && $quizId > 0) {
            $query->where('quiz_results.quiz_id', $quizId);
        }

        $jabatan = trim((string) ($filters['jabatan'] ?? ''));
        if ($jabatan !== '') {
            $jabatan = ParticipantAppliedForNormalizer::normalize($jabatan);
            $query->whereRaw('LOWER(quiz_attempts.participant_applied_for) = ?', [mb_strtolower($jabatan)]);
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && $status !== 'all') {
            $query->where('quiz_results.result_status', $status);
        }

        $startAt = $filters['start_at'] ?? null;
        $endAt = $filters['end_at'] ?? null;
        if ($startAt instanceof CarbonImmutable) {
            $query->where('quiz_attempts.submitted_at', '>=', $startAt);
        }
        if ($endAt instanceof CarbonImmutable) {
            $query->where('quiz_attempts.submitted_at', '<=', $endAt);
        }

        return $query
            ->orderBy('quiz_attempts.submitted_at')
            ->orderBy('quiz_results.id')
            ->get()
            ->map(function ($row) {
                $submittedAt = $row->submitted_at ? CarbonImmutable::parse($row->submitted_at, self::TZ) : null;

                return [
                    'nama' => (string) ($row->participant_name ?? ''),
                    'jabatan' => (string) ($row->participant_applied_for ?? ''),
                    'tanggal_submit' => $submittedAt ? $submittedAt->format('Y-m-d') : '',
                    'jumlah_benar' => (int) ($row->correct_answers ?? 0),
                    'jumlah_salah' => (int) ($row->wrong_answers ?? 0),
                    'jumlah_kosong' => (int) ($row->unanswered_answers ?? 0),
                    'total_soal' => (int) ($row->total_questions ?? 0),
                    'grade' => (string) ($row->grade_letter ?? ''),
                    'link_drive' => (string) ($row->google_drive_url ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function applyGradeStyle(\PhpOffice\PhpSpreadsheet\Style\Style $style, string $gradeLetter): void
    {
        $hex = match (strtoupper(trim($gradeLetter))) {
            'A' => '16A34A',
            'B' => '2563EB',
            'C' => 'CA8A04',
            'D' => 'EA580C',
            default => 'DC2626',
        };

        $style->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF'.$hex],
            ],
        ]);
    }

    private function applyHeaderMeta(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $filters): void
    {
        $company = (string) config('app.name');
        $exportedAt = CarbonImmutable::now(self::TZ)->locale('id')->translatedFormat('d F Y');
        $period = $this->periodLabel($filters);

        $sheet->setCellValueExplicit('A2', 'EXPORT HASIL TEST', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('A3', trim($company).'  |  Periode: '.$period.'  |  Diekspor: '.$exportedAt, DataType::TYPE_STRING);
    }

    private function periodLabel(array $filters): string
    {
        $startAt = $filters['start_at'] ?? null;
        $endAt = $filters['end_at'] ?? null;

        if ($startAt instanceof CarbonImmutable && $endAt instanceof CarbonImmutable) {
            $a = $startAt->locale('id')->translatedFormat('d F Y');
            $b = $endAt->locale('id')->translatedFormat('d F Y');
            return $a === $b ? $a : ($a.' - '.$b);
        }

        if ($startAt instanceof CarbonImmutable) {
            return $startAt->locale('id')->translatedFormat('d F Y').' - '.$this->nowLabel();
        }

        if ($endAt instanceof CarbonImmutable) {
            return 'sampai '.$endAt->locale('id')->translatedFormat('d F Y');
        }

        return $this->nowLabel();
    }

    private function nowLabel(): string
    {
        return CarbonImmutable::now(self::TZ)->locale('id')->translatedFormat('d F Y');
    }

    private function downloadFileName(array $filters): string
    {
        $now = CarbonImmutable::now(self::TZ)->format('Ymd_His');
        return 'Export Hasil Test '.$now.'.xlsx';
    }
}
