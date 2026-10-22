<?php

namespace App\Services\Export;

use App\Models\Classroom;
use App\Models\Period;
use App\Services\Report\ReportCardBuilder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LedgerExcelExporter
{
    public function __construct(
        protected ReportCardBuilder $builder = new ReportCardBuilder
    ) {}

    public function export(Classroom $classroom, Period $period): Spreadsheet
    {
        $data = $this->builder->buildLedgerData($classroom, $period);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheetTitle = substr(preg_replace('/[^A-Za-z0-9_-]/', '', 'Leger_'.$classroom->name), 0, 31);
        $sheet->setTitle($sheetTitle ?: 'Leger');

        $cols = $data['subjectColumns'];
        $rows = $data['rows'];

        $totalCols = 3 + count($cols) + 3; // No, NISN, Nama + subjects + Jumlah, Rerata, Rank
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        // Baris 1: Judul
        $sheet->setCellValue('A1', 'LEGER NILAI SUMATIF TENGAH SEMESTER');
        $sheet->mergeCells('A1:'.$lastColLetter.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Baris 2: Sub-judul
        $subTitle = 'Kelas: '.$classroom->name.' | Semester: '.$period->semester->label().' | Tahun Pelajaran: '.$period->academicYear->name;
        $sheet->setCellValue('A2', $subTitle);
        $sheet->mergeCells('A2:'.$lastColLetter.'2');
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Baris 4: Header tabel
        $headerRow = 4;
        $sheet->setCellValue('A'.$headerRow, 'No');
        $sheet->setCellValue('B'.$headerRow, 'NISN');
        $sheet->setCellValue('C'.$headerRow, 'Nama Siswa');

        $colIdx = 4;
        foreach ($cols as $subjectCol) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($colLetter.$headerRow, $subjectCol['code']);
            $colIdx++;
        }

        $colJumlahLetter = Coordinate::stringFromColumnIndex($colIdx++);
        $colRerataLetter = Coordinate::stringFromColumnIndex($colIdx++);
        $colRankLetter = Coordinate::stringFromColumnIndex($colIdx++);

        $sheet->setCellValue($colJumlahLetter.$headerRow, 'Jumlah');
        $sheet->setCellValue($colRerataLetter.$headerRow, 'Rerata');
        $sheet->setCellValue($colRankLetter.$headerRow, 'Rank');

        // Style header baris 4
        $headerRange = 'A'.$headerRow.':'.$lastColLetter.$headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F3F5');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Baris data mulai baris 5
        $currentRow = 5;
        $no = 1;

        foreach ($rows as $item) {
            $student = $item['student'];

            $sheet->setCellValue('A'.$currentRow, $no++);
            $sheet->setCellValueExplicit('B'.$currentRow, $student->nisn, DataType::TYPE_STRING);
            $sheet->setCellValue('C'.$currentRow, $student->name);

            $subjectColIdx = 4;
            foreach ($cols as $subjectCol) {
                $colLetter = Coordinate::stringFromColumnIndex($subjectColIdx);
                $scoreData = $item['scores'][$subjectCol['id']] ?? null;
                $score = $scoreData ? $scoreData['score'] : null;

                if ($score !== null) {
                    $sheet->setCellValue($colLetter.$currentRow, $score);
                    // Tandai bila di bawah KKTP
                    if ($scoreData['is_below'] ?? false) {
                        $sheet->getStyle($colLetter.$currentRow)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF8D7DA'); // Soft red / pink
                    }
                } else {
                    $sheet->setCellValue($colLetter.$currentRow, '–');
                }
                $sheet->getStyle($colLetter.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $subjectColIdx++;
            }

            $sheet->setCellValue($colJumlahLetter.$currentRow, $item['total']);
            $sheet->setCellValue($colRerataLetter.$currentRow, $item['formatted_average']);
            $sheet->setCellValue($colRankLetter.$currentRow, $item['rank']);

            $sheet->getStyle('A'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B'.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colJumlahLetter.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colRerataLetter.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colRankLetter.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $currentRow++;
        }

        // Baris Rata-rata Kelas
        $avgRow = $currentRow;
        $sheet->setCellValue('A'.$avgRow, 'Rata-rata Kelas');
        $sheet->mergeCells('A'.$avgRow.':C'.$avgRow);
        $sheet->getStyle('A'.$avgRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$avgRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $subjectColIdx = 4;
        foreach ($cols as $subjectCol) {
            $colLetter = Coordinate::stringFromColumnIndex($subjectColIdx);
            $sheet->setCellValue($colLetter.$avgRow, $data['classAverages'][$subjectCol['id']] ?? '–');
            $sheet->getStyle($colLetter.$avgRow)->getFont()->setBold(true);
            $sheet->getStyle($colLetter.$avgRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $subjectColIdx++;
        }

        $sheet->setCellValue($colJumlahLetter.$avgRow, '');
        $sheet->setCellValue($colRerataLetter.$avgRow, $data['overallAverage']);
        $sheet->getStyle($colRerataLetter.$avgRow)->getFont()->setBold(true);
        $sheet->getStyle($colRerataLetter.$avgRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue($colRankLetter.$avgRow, '');

        $sheet->getStyle('A'.$avgRow.':'.$lastColLetter.$avgRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF8F9FA');

        // Border seluruh tabel
        $tableRange = 'A'.$headerRow.':'.$lastColLetter.$avgRow;
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto size columns
        for ($i = 1; $i <= $totalCols; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
