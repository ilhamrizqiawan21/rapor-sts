<?php

namespace App\Services\Import;

use App\Models\Classroom;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TemplateBuilder
{
    public function buildTeacherTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('guru');

        // Baris 1: Judul
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA GURU');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Baris 2: Keterangan
        $sheet->setCellValue('A2', 'Petunjuk: NIK/NUPTK wajib diisi (dipakai sebagai username). L/P diisi L atau P. Password kosong = default "password". Data mulai baris 4.');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);

        // Baris 3: Header
        $headers = ['NIK/NUPTK', 'NIP', 'Nama (dengan gelar)', 'L/P', 'Password awal'];
        $cols = ['A', 'B', 'C', 'D', 'E'];

        foreach ($headers as $idx => $h) {
            $cell = $cols[$idx].'3';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F3F5');
            $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Contoh di baris 4
        $sheet->setCellValueExplicit('A4', '3204123456780001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B4', '198001012005011001', DataType::TYPE_STRING);
        $sheet->setCellValue('C4', 'Ahmad Dahlan, S.Pd.I');
        $sheet->setCellValue('D4', 'L');
        $sheet->setCellValue('E4', 'password123');

        // Auto size columns
        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public function buildStudentTemplate(?Classroom $classroom = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('siswa');

        $className = $classroom ? $classroom->name : 'SEMUA KELAS';

        // Baris 1: Judul
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA SISWA — KELAS '.$className);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Baris 2: Keterangan
        $sheet->setCellValue('A2', 'Petunjuk: NISN wajib 10 digit dan unik. Tanggal lahir format DD-MM-YYYY atau format tanggal Excel. Data mulai baris 4.');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);

        // Baris 3: Header
        $headers = ['NIS', 'NISN', 'Nama', 'L/P', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $idx => $h) {
            $cell = $cols[$idx].'3';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F3F5');
            $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Contoh di baris 4
        $sheet->setCellValueExplicit('A4', '26001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B4', '0123456789', DataType::TYPE_STRING);
        $sheet->setCellValue('C4', 'MUHAMMAD FADIL');
        $sheet->setCellValue('D4', 'L');
        $sheet->setCellValue('E4', 'Bandung');
        $sheet->setCellValue('F4', '15-05-2012');
        $sheet->setCellValue('G4', 'Jl. Galanggang No. 10');

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
