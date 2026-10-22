<?php

namespace App\Services\Import;

use App\Models\Classroom;
use App\Models\TeachingAssignment;
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

    public function buildSummativeTemplate(TeachingAssignment $assignment): Spreadsheet
    {
        $assignment->loadMissing([
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $assignment->period->academic_year_id)->with('student'),
            'learningScopes.scores',
            'subject',
        ]);

        $students = $assignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $scopes = $assignment->learningScopes()->orderBy('sequence')->get();

        $spreadsheet = new Spreadsheet;

        $sheetsToCreate = [];
        if ($scopes->isNotEmpty()) {
            foreach ($scopes as $scope) {
                $sheetsToCreate[] = [
                    'title' => 'LM'.$scope->sequence,
                    'summary' => $scope->summary,
                    'kktp' => (float) $scope->kktp,
                    'scores' => $scope->scores->keyBy('student_id'),
                ];
            }
            $nextSeq = $scopes->max('sequence') + 1;
            $sheetsToCreate[] = [
                'title' => 'LM'.$nextSeq,
                'summary' => '',
                'kktp' => (float) $assignment->kktp,
                'scores' => collect(),
            ];
        } else {
            $sheetsToCreate[] = [
                'title' => 'LM1',
                'summary' => '',
                'kktp' => (float) $assignment->kktp,
                'scores' => collect(),
            ];
        }

        foreach ($sheetsToCreate as $idx => $def) {
            $sheet = ($idx === 0) ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($def['title']);

            // Sel B1: Ringkasan LM
            $sheet->setCellValue('A1', 'Ringkasan LM:');
            $sheet->setCellValue('B1', $def['summary']);
            $sheet->getStyle('A1')->getFont()->setBold(true);

            // Sel B2: KKTP LM
            $sheet->setCellValue('A2', 'KKTP LM:');
            $sheet->setCellValue('B2', $def['kktp']);
            $sheet->getStyle('A2')->getFont()->setBold(true);

            // Header baris 3
            $headers = ['ID', 'NISN', 'Nama', 'L/P', 'Nilai'];
            $cols = ['A', 'B', 'C', 'D', 'E'];

            foreach ($headers as $cIdx => $h) {
                $cell = $cols[$cIdx].'3';
                $sheet->setCellValue($cell, $h);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F3F5');
                $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }

            // Data siswa mulai baris 4
            $row = 4;
            foreach ($students as $student) {
                $score = $def['scores']->get($student->id)?->score;

                $sheet->setCellValue('A'.$row, $student->id);
                $sheet->setCellValueExplicit('B'.$row, $student->nisn, DataType::TYPE_STRING);
                $sheet->setCellValue('C'.$row, $student->name);
                $sheet->setCellValue('D'.$row, $student->gender);
                if ($score !== null) {
                    $sheet->setCellValue('E'.$row, (float) $score);
                }
                $row++;
            }

            foreach ($cols as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }

    public function buildStsTemplate(TeachingAssignment $assignment): Spreadsheet
    {
        $assignment->loadMissing([
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $assignment->period->academic_year_id)->with('student'),
            'stsScores',
            'subject',
        ]);

        $students = $assignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $stsScores = $assignment->stsScores->keyBy('student_id');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('STS');

        // Baris 1: Judul
        $sheet->setCellValue('A1', 'TEMPLATE NILAI STS — '.$assignment->subject->name.' '.$assignment->classroom->name);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Baris 2: Keterangan
        $sheet->setCellValue('A2', 'Nilai Batas Ketercapaian STS: '.((float) $assignment->kktp).'. Isi nilai 0-100 pada kolom E. Kosongkan jika siswa belum ada nilai.');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);

        // Header baris 3
        $headers = ['ID', 'NISN', 'Nama', 'L/P', 'Nilai STS'];
        $cols = ['A', 'B', 'C', 'D', 'E'];

        foreach ($headers as $idx => $h) {
            $cell = $cols[$idx].'3';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F3F5');
            $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Baris data mulai baris 4
        $row = 4;
        foreach ($students as $student) {
            $score = $stsScores->get($student->id)?->score;

            $sheet->setCellValue('A'.$row, $student->id);
            $sheet->setCellValueExplicit('B'.$row, $student->nisn, DataType::TYPE_STRING);
            $sheet->setCellValue('C'.$row, $student->name);
            $sheet->setCellValue('D'.$row, $student->gender);
            if ($score !== null) {
                $sheet->setCellValue('E'.$row, (float) $score);
            }
            $row++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
