<?php

namespace App\Services\Export;

use App\Models\Classroom;
use App\Models\Period;
use App\Services\Report\ReportCardBuilder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LedgerExcelExporter
{
    public function __construct(
        protected ReportCardBuilder $builder = new ReportCardBuilder
    ) {}

    public function export(Classroom $classroom, Period $period): Spreadsheet
    {
        $data = $this->builder->buildLedgerData($classroom, $period);

        $spreadsheet = new Spreadsheet;

        // Sheet 1: Pengetahuan
        $sheetPengetahuan = $spreadsheet->getActiveSheet();
        $sheetPengetahuan->setTitle('Pengetahuan');
        $this->renderSheet(
            sheet: $sheetPengetahuan,
            title: 'LEGGER NILAI PENGETAHUAN',
            classroom: $classroom,
            period: $period,
            data: $data,
            rows: $data['rowsPengetahuan'],
            classAverages: $data['classAveragesPengetahuan'],
            overallAverage: $data['overallAveragePengetahuan']
        );

        // Sheet 2: Keterampilan
        $sheetKeterampilan = $spreadsheet->createSheet();
        $sheetKeterampilan->setTitle('Keterampilan');
        $this->renderSheet(
            sheet: $sheetKeterampilan,
            title: 'LEGGER NILAI KETERAMPILAN',
            classroom: $classroom,
            period: $period,
            data: $data,
            rows: $data['rowsKeterampilan'],
            classAverages: $data['classAveragesKeterampilan'],
            overallAverage: $data['overallAverageKeterampilan']
        );

        // Set active sheet back to Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, array>  $rows
     * @param  array<int, string>  $classAverages
     */
    protected function renderSheet(
        Worksheet $sheet,
        string $title,
        Classroom $classroom,
        Period $period,
        array $data,
        $rows,
        array $classAverages,
        string $overallAverage
    ): void {
        $profile = $data['profile'];
        $columnGroups = $data['columnGroups'] ?? [];
        $leafCols = $data['subjectColumns'] ?? [];

        // Row 1: Judul Utama
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Row 2: Metadata Kelas & Semester
        $sheet->setCellValue('A2', 'Kelas:');
        $sheet->setCellValueExplicit('B2', $classroom->name, DataType::TYPE_STRING);
        $sheet->setCellValue('D2', 'Semester:');
        $sheet->setCellValue('E2', $period->semester->label());
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('D2')->getFont()->setBold(true);

        // Row 3: Metadata Madrasah & Tahun Ajaran
        $sheet->setCellValue('A3', 'Madrasah:');
        $sheet->setCellValueExplicit('B3', $profile->name ?? 'Madrasah', DataType::TYPE_STRING);
        $sheet->setCellValue('D3', 'Tahun Ajaran:');
        $sheet->setCellValue('E3', $period->academicYear->name);
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('D3')->getFont()->setBold(true);

        // Header baris 6 & 7
        // Kolom identitas: No, NIS, Nisn, Nama, JK (5 kolom: A, B, C, D, E)
        $sheet->mergeCells('A6:A7');
        $sheet->setCellValue('A6', 'No');

        $sheet->mergeCells('B6:B7');
        $sheet->setCellValue('B6', 'NIS');

        $sheet->mergeCells('C6:C7');
        $sheet->setCellValue('C6', 'Nisn');

        $sheet->mergeCells('D6:D7');
        $sheet->setCellValue('D6', 'Nama');

        $sheet->mergeCells('E6:E7');
        $sheet->setCellValue('E6', 'JK');

        // Susun kolom mapel berdasar columnGroups
        $colIdx = 6; // Mulai dari Kolom F
        foreach ($columnGroups as $cg) {
            if (! empty($cg['is_group'])) {
                $subCols = $cg['columns'] ?? [];
                $subCount = count($subCols);
                if ($subCount > 0) {
                    $startLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $endLetter = Coordinate::stringFromColumnIndex($colIdx + $subCount - 1);

                    if ($startLetter !== $endLetter) {
                        $sheet->mergeCells("{$startLetter}6:{$endLetter}6");
                    }
                    $sheet->setCellValueExplicit("{$startLetter}6", $cg['code'], DataType::TYPE_STRING);

                    foreach ($subCols as $sc) {
                        $cLetter = Coordinate::stringFromColumnIndex($colIdx);
                        $sheet->setCellValueExplicit("{$cLetter}7", $sc['code'], DataType::TYPE_STRING);
                        $colIdx++;
                    }
                }
            } else {
                $cLetter = Coordinate::stringFromColumnIndex($colIdx);
                $sheet->mergeCells("{$cLetter}6:{$cLetter}7");
                $sheet->setCellValueExplicit("{$cLetter}6", $cg['code'], DataType::TYPE_STRING);
                $colIdx++;
            }
        }

        // Kolom ringkasan: Jumlah, Rerata, Rank
        $colJumlahLetter = Coordinate::stringFromColumnIndex($colIdx++);
        $colRerataLetter = Coordinate::stringFromColumnIndex($colIdx++);
        $colRankLetter = Coordinate::stringFromColumnIndex($colIdx++);

        $sheet->mergeCells("{$colJumlahLetter}6:{$colJumlahLetter}7");
        $sheet->setCellValue("{$colJumlahLetter}6", 'Jumlah');

        $sheet->mergeCells("{$colRerataLetter}6:{$colRerataLetter}7");
        $sheet->setCellValue("{$colRerataLetter}6", 'Rerata');

        $sheet->mergeCells("{$colRankLetter}6:{$colRankLetter}7");
        $sheet->setCellValue("{$colRankLetter}6", 'Rank');

        $lastColLetter = Coordinate::stringFromColumnIndex($colIdx - 1);
        $totalCols = $colIdx - 1;

        // Styling Header Range
        $headerRange = "A6:{$lastColLetter}7";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F3F5');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Baris data mulai baris 8
        $currentRow = 8;
        $no = 1;

        foreach ($rows as $item) {
            $student = $item['student'];

            $sheet->setCellValue("A{$currentRow}", $no++);
            $sheet->setCellValueExplicit("B{$currentRow}", (string) $student->nis, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$currentRow}", (string) $student->nisn, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$currentRow}", $student->name, DataType::TYPE_STRING);
            $sheet->setCellValue("E{$currentRow}", $student->gender ?? '–');

            $subjectColIdx = 6;
            foreach ($leafCols as $col) {
                $cLetter = Coordinate::stringFromColumnIndex($subjectColIdx);
                $scoreData = $item['scores'][$col['id']] ?? null;
                $score = $scoreData ? $scoreData['score'] : null;

                if ($score !== null) {
                    $sheet->setCellValue("{$cLetter}{$currentRow}", $score);
                    if ($scoreData['is_below'] ?? false) {
                        $sheet->getStyle("{$cLetter}{$currentRow}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF8D7DA');
                    }
                } else {
                    $sheet->setCellValue("{$cLetter}{$currentRow}", '–');
                }
                $sheet->getStyle("{$cLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $subjectColIdx++;
            }

            $sheet->setCellValue("{$colJumlahLetter}{$currentRow}", $item['total']);
            $sheet->setCellValue("{$colRerataLetter}{$currentRow}", $item['formatted_average']);
            $sheet->setCellValue("{$colRankLetter}{$currentRow}", $item['rank']);

            $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colJumlahLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colRerataLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$colRankLetter}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $currentRow++;
        }

        // Baris Rata-rata Kelas
        $avgRow = $currentRow;
        $sheet->mergeCells("A{$avgRow}:E{$avgRow}");
        $sheet->setCellValue("A{$avgRow}", 'Rata-rata Kelas');
        $sheet->getStyle("A{$avgRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$avgRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $subjectColIdx = 6;
        foreach ($leafCols as $col) {
            $cLetter = Coordinate::stringFromColumnIndex($subjectColIdx);
            $sheet->setCellValue("{$cLetter}{$avgRow}", $classAverages[$col['id']] ?? '–');
            $sheet->getStyle("{$cLetter}{$avgRow}")->getFont()->setBold(true);
            $sheet->getStyle("{$cLetter}{$avgRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $subjectColIdx++;
        }

        $sheet->setCellValue("{$colJumlahLetter}{$avgRow}", '');
        $sheet->setCellValue("{$colRerataLetter}{$avgRow}", $overallAverage);
        $sheet->getStyle("{$colRerataLetter}{$avgRow}")->getFont()->setBold(true);
        $sheet->getStyle("{$colRerataLetter}{$avgRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue("{$colRankLetter}{$avgRow}", '');

        $sheet->getStyle("A{$avgRow}:{$lastColLetter}{$avgRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF8F9FA');

        // Border seluruh tabel
        $tableRange = "A6:{$lastColLetter}{$avgRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto size columns
        for ($i = 1; $i <= $totalCols; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }
}
