<?php

namespace App\Services\Export;

use App\Models\Student;
use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class RdmExporter
{
    /**
     * Ekspor nilai sumatif lingkup materi ke dalam template RDM (Kurikulum Merdeka).
     *
     * @param  TeachingAssignment  $assignment  Penugasan mengajar
     * @param  string|null  $templatePath  Path berkas template asli (bila null memakai template bawaan)
     * @param  string  $version  Versi RDM (default '3.1')
     */
    public function exportSummative(
        TeachingAssignment $assignment,
        ?string $templatePath = null,
        string $version = '3.1'
    ): Spreadsheet {
        $versions = config('rdm_export.versions', []);
        $cfg = $versions[$version]['sumatif'] ?? null;
        if (! $cfg) {
            throw new RuntimeException("Konfigurasi template RDM versi {$version} untuk sumatif tidak ditemukan.");
        }

        $sourcePath = $templatePath ?? $cfg['template_path'];
        if (! file_exists($sourcePath)) {
            throw new RuntimeException("Berkas template RDM tidak ditemukan di: {$sourcePath}");
        }

        $assignment->loadMissing([
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $assignment->period->academic_year_id)->with('student'),
            'subject',
            'period.academicYear',
            'learningScopes' => fn ($q) => $q->orderBy('sequence')->with(['scores.student']),
        ]);

        $activeStudents = $assignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $scopes = $assignment->learningScopes;

        $spreadsheet = IOFactory::load($sourcePath);

        $isCustomUpload = ($templatePath !== null);

        foreach ($scopes as $scope) {
            $sheetName = ($cfg['sheet_prefix'] ?? 'SUM ').$scope->sequence;
            $sheet = $spreadsheet->getSheetByName($sheetName);

            if (! $sheet) {
                continue;
            }

            // Isi header
            $this->fillHeaderCells(
                sheet: $sheet,
                cfg: $cfg,
                name: 'Sumatif '.$scope->sequence,
                topic: (string) $scope->summary,
                classSubject: $assignment->classroom->name.'/'.$assignment->subject->name
            );

            // Indeks nilai siswa pada LM ini
            $scoresByStudentId = $scope->scores->keyBy('student_id');

            $this->fillStudentScores(
                sheet: $sheet,
                cfg: $cfg,
                activeStudents: $activeStudents,
                scoreProvider: fn ($student) => $scoresByStudentId->get($student->id)?->score,
                isCustomUpload: $isCustomUpload
            );
        }

        return $spreadsheet;
    }

    /**
     * Ekspor nilai tes STS ke dalam template RDM (Kurikulum Merdeka / PAT-PAS-STS).
     *
     * @param  TeachingAssignment  $assignment  Penugasan mengajar
     * @param  string|null  $templatePath  Path berkas template asli (bila null memakai template bawaan)
     * @param  string  $version  Versi RDM (default '3.1')
     */
    public function exportSts(
        TeachingAssignment $assignment,
        ?string $templatePath = null,
        string $version = '3.1'
    ): Spreadsheet {
        $versions = config('rdm_export.versions', []);
        $cfg = $versions[$version]['tes'] ?? null;
        if (! $cfg) {
            throw new RuntimeException("Konfigurasi template RDM versi {$version} untuk tes tidak ditemukan.");
        }

        $sourcePath = $templatePath ?? $cfg['template_path'];
        if (! file_exists($sourcePath)) {
            throw new RuntimeException("Berkas template RDM tidak ditemukan di: {$sourcePath}");
        }

        $assignment->loadMissing([
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $assignment->period->academic_year_id)->with('student'),
            'subject',
            'period.academicYear',
            'stsScores.student',
        ]);

        $activeStudents = $assignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $stsScoresByStudentId = $assignment->stsScores->keyBy('student_id');

        $spreadsheet = IOFactory::load($sourcePath);

        // Cari sheet tes pertama yang cocok
        $sheet = null;
        $sheetNames = $cfg['sheet_names'] ?? ['PAT 1', 'PAS 1', 'STS 1', 'SAS 1'];
        foreach ($sheetNames as $candidate) {
            $candidateSheet = $spreadsheet->getSheetByName($candidate);
            if ($candidateSheet) {
                $sheet = $candidateSheet;
                break;
            }
        }

        if (! $sheet) {
            $sheet = $spreadsheet->getSheet(0);
        }

        $this->fillHeaderCells(
            sheet: $sheet,
            cfg: $cfg,
            name: 'STS 1',
            topic: 'STS '.$assignment->subject->name,
            classSubject: $assignment->classroom->name.'/'.$assignment->subject->name
        );

        $isCustomUpload = ($templatePath !== null);

        $this->fillStudentScores(
            sheet: $sheet,
            cfg: $cfg,
            activeStudents: $activeStudents,
            scoreProvider: fn ($student) => $stsScoresByStudentId->get($student->id)?->score,
            isCustomUpload: $isCustomUpload
        );

        return $spreadsheet;
    }

    /**
     * Mengisi metadata header pada sheet template.
     *
     * @param  array<string, mixed>  $cfg
     */
    protected function fillHeaderCells(
        Worksheet $sheet,
        array $cfg,
        string $name,
        string $topic,
        string $classSubject
    ): void {
        if (! empty($cfg['cell_name'])) {
            $sheet->setCellValue($cfg['cell_name'], $name);
        }

        if (! empty($cfg['cell_topic'])) {
            $sheet->setCellValue($cfg['cell_topic'], $topic);
        }

        if (! empty($cfg['cell_class_subject'])) {
            $sheet->setCellValue($cfg['cell_class_subject'], $classSubject);
        }
    }

    /**
     * Mengisi baris data siswa & nilai pada worksheet.
     *
     * @param  array<string, mixed>  $cfg
     * @param  Collection<int, Student>  $activeStudents
     * @param  callable(Student): (float|int|null)  $scoreProvider
     */
    protected function fillStudentScores(
        Worksheet $sheet,
        array $cfg,
        Collection $activeStudents,
        callable $scoreProvider,
        bool $isCustomUpload
    ): void {
        $startRow = (int) ($cfg['start_row'] ?? 7);
        $colNo = $cfg['col_no'] ?? 'A';
        $colIdSiswa = $cfg['col_id_siswa'] ?? 'B';
        $colNis = $cfg['col_nis'] ?? 'C';
        $colNisn = $cfg['col_nisn'] ?? 'D';
        $colNama = $cfg['col_nama'] ?? 'E';
        $colNilai = $cfg['col_nilai'] ?? 'F';

        $highestRow = $sheet->getHighestRow();

        // Bangun peta pencarian siswa dalam database kita
        $studentsByNisn = [];
        $studentsByNis = [];
        foreach ($activeStudents as $student) {
            $cleanNisn = trim((string) $student->nisn);
            if ($cleanNisn !== '') {
                $studentsByNisn[$cleanNisn] = $student;
            }
            $cleanNis = trim((string) $student->nis);
            if ($cleanNis !== '') {
                $studentsByNis[$cleanNis] = $student;
            }
        }

        // Periksa apakah template memiliki baris yang cocok dengan NISN/NIS siswa kita
        $matchedRows = 0;
        for ($r = $startRow; $r <= $highestRow; $r++) {
            $rowNisn = trim((string) $sheet->getCell("{$colNisn}{$r}")->getValue());
            $rowNis = trim((string) $sheet->getCell("{$colNis}{$r}")->getValue());

            if (($rowNisn !== '' && isset($studentsByNisn[$rowNisn])) ||
                ($rowNis !== '' && isset($studentsByNis[$rowNis]))) {
                $matchedRows++;
            }
        }

        if ($isCustomUpload || $matchedRows > 0) {
            // Skenario 1: File template memiliki siswa yang sudah cocok (atau berkas upload resmi dari RDM).
            // Kita HANYA mengisi kolom nilai, mempertahankan kolom ID Siswa internal RDM.
            for ($r = $startRow; $r <= $highestRow; $r++) {
                $rowNisn = trim((string) $sheet->getCell("{$colNisn}{$r}")->getValue());
                $rowNis = trim((string) $sheet->getCell("{$colNis}{$r}")->getValue());
                $rowNama = trim((string) $sheet->getCell("{$colNama}{$r}")->getValue());

                if ($rowNisn === '' && $rowNis === '' && $rowNama === '') {
                    continue;
                }

                $student = null;
                if ($rowNisn !== '' && isset($studentsByNisn[$rowNisn])) {
                    $student = $studentsByNisn[$rowNisn];
                } elseif ($rowNis !== '' && isset($studentsByNis[$rowNis])) {
                    $student = $studentsByNis[$rowNis];
                }

                if ($student) {
                    $scoreVal = $scoreProvider($student);
                    if ($scoreVal !== null) {
                        $sheet->setCellValue("{$colNilai}{$r}", (float) $scoreVal);
                    }
                }
            }
        } else {
            // Skenario 2: Template bawaan digunakan langsung untuk kelas kita yang NISN-nya berbeda dari sampel.
            // Kita populasi daftar siswa kelas ke baris template agar hasil unduh lengkap.
            $currentRow = $startRow;
            foreach ($activeStudents as $idx => $student) {
                $sheet->setCellValue("{$colNo}{$currentRow}", $idx + 1);
                $sheet->setCellValueExplicit("{$colNis}{$currentRow}", (string) $student->nis, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("{$colNisn}{$currentRow}", (string) $student->nisn, DataType::TYPE_STRING);
                $sheet->setCellValue("{$colNama}{$currentRow}", $student->name);

                $scoreVal = $scoreProvider($student);
                if ($scoreVal !== null) {
                    $sheet->setCellValue("{$colNilai}{$currentRow}", (float) $scoreVal);
                } else {
                    $sheet->setCellValue("{$colNilai}{$currentRow}", null);
                }

                $currentRow++;
            }

            // Bersihkan baris sisa di bawah siswa kita bila ada baris contoh lama
            for ($r = $currentRow; $r <= $highestRow; $r++) {
                $rowNisn = trim((string) $sheet->getCell("{$colNisn}{$r}")->getValue());
                $rowNama = trim((string) $sheet->getCell("{$colNama}{$r}")->getValue());
                if ($rowNisn !== '' || $rowNama !== '') {
                    $sheet->setCellValue("{$colNo}{$r}", null);
                    $sheet->setCellValue("{$colIdSiswa}{$r}", null);
                    $sheet->setCellValue("{$colNis}{$r}", null);
                    $sheet->setCellValue("{$colNisn}{$r}", null);
                    $sheet->setCellValue("{$colNama}{$r}", null);
                    $sheet->setCellValue("{$colNilai}{$r}", null);
                }
            }
        }
    }
}
