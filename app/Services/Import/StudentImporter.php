<?php

namespace App\Services\Import;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StudentImporter
{
    /**
     * @return array{success: bool, imported: int, errors: array<string>}
     */
    public function import(UploadedFile|string $file, Classroom $classroom): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak dapat dibaca: '.$e->getMessage()],
            ];
        }
        $sheet = $spreadsheet->getSheetByName('siswa') ?? $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow < 4) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak memiliki baris data siswa yang dapat diimpor (minimal baris 4).'],
            ];
        }

        $rowsToImport = [];
        $errors = [];
        $totalDataRows = 0;

        for ($row = 4; $row <= $highestRow; $row++) {
            $nis = trim((string) $sheet->getCell("A{$row}")->getValue());
            $nisn = trim((string) $sheet->getCell("B{$row}")->getValue());
            $name = trim((string) $sheet->getCell("C{$row}")->getValue());
            $gender = strtoupper(trim((string) $sheet->getCell("D{$row}")->getValue()));
            $birthPlace = trim((string) $sheet->getCell("E{$row}")->getValue());
            $birthDateRaw = $sheet->getCell("F{$row}")->getValue();
            $address = trim((string) $sheet->getCell("G{$row}")->getValue());

            // Lewati baris kosong total
            if ($nisn === '' && $name === '') {
                continue;
            }

            $totalDataRows++;

            if ($nisn === '') {
                $errors[] = "Baris {$row}: NISN wajib diisi.";

                continue;
            }

            if ($name === '') {
                $errors[] = "Baris {$row}: Nama siswa wajib diisi.";

                continue;
            }

            if (! in_array($gender, ['L', 'P'], true)) {
                $errors[] = "Baris {$row}: Jenis kelamin harus 'L' atau 'P'.";

                continue;
            }

            // Parsing tanggal lahir
            $birthDate = null;
            if (is_numeric($birthDateRaw)) {
                $birthDate = Date::excelToDateTimeObject((float) $birthDateRaw)->format('Y-m-d');
            } elseif (is_string($birthDateRaw) && trim($birthDateRaw) !== '') {
                try {
                    $birthDate = Carbon::parse(trim($birthDateRaw))->format('Y-m-d');
                } catch (\Throwable) {
                    $errors[] = "Baris {$row}: Format tanggal lahir '{$birthDateRaw}' tidak valid (gunakan DD-MM-YYYY).";

                    continue;
                }
            }

            if (! $birthDate) {
                $birthDate = '2012-01-01'; // Fallback aman bila kosong
            }

            $rowsToImport[] = [
                'nis' => $nis !== '' ? $nis : null,
                'nisn' => $nisn,
                'name' => strtoupper($name),
                'gender' => $gender,
                'birth_place' => $birthPlace !== '' ? $birthPlace : '-',
                'birth_date' => $birthDate,
                'address' => $address !== '' ? $address : null,
                'is_active' => true,
            ];
        }

        if ($totalDataRows > 0 && (count($errors) / $totalDataRows) > 0.20) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => array_merge(
                    ['Gagal: Lebih dari 20% baris data memiliki kesalahan. Seluruh file dibatalkan.'],
                    $errors
                ),
            ];
        }

        $importedCount = 0;
        DB::transaction(function () use ($rowsToImport, $classroom, &$importedCount) {
            foreach ($rowsToImport as $data) {
                $student = Student::updateOrCreate(
                    ['nisn' => $data['nisn']],
                    $data
                );

                // Enrollment: satu siswa satu kelas per tahun pelajaran (unique: academic_year_id, student_id)
                Enrollment::updateOrCreate(
                    [
                        'academic_year_id' => $classroom->academic_year_id,
                        'student_id' => $student->id,
                    ],
                    [
                        'classroom_id' => $classroom->id,
                    ]
                );

                $importedCount++;
            }
        });

        ActivityLogger::log('import_students', "Berhasil mengimpor {$importedCount} data siswa ke kelas {$classroom->name}.");

        return [
            'success' => true,
            'imported' => $importedCount,
            'errors' => $errors,
        ];
    }
}
