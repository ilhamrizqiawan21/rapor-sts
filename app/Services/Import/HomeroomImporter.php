<?php

namespace App\Services\Import;

use App\Models\Classroom;
use App\Models\HomeroomRecord;
use App\Models\Period;
use App\Services\ActivityLogger;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HomeroomImporter
{
    /**
     * @return array{success: bool, imported: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file, Classroom $classroom, Period $period): array
    {
        if ($classroom->isLockedInPeriod($period->id)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ["Kelas {$classroom->name} sudah dikunci. Perubahan tidak diizinkan."],
            ];
        }

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (Exception $e) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak dapat dibaca: '.$e->getMessage()],
            ];
        }

        $classroom->loadMissing([
            'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
        ]);

        $enrolledStudents = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->keyBy('id');

        $enrolledStudentsByNisn = $enrolledStudents->keyBy('nisn');

        $sheet = $spreadsheet->getSheetByName('Kehadiran_Catatan') ?? $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $totalRows = 0;
        $errorList = [];
        $validRecords = [];

        for ($row = 4; $row <= $highestRow; $row++) {
            $studentId = trim((string) $sheet->getCell('A'.$row)->getValue());
            $nisn = trim((string) $sheet->getCell('B'.$row)->getValue());
            $sickRaw = $sheet->getCell('D'.$row)->getValue();
            $permitRaw = $sheet->getCell('E'.$row)->getValue();
            $absentRaw = $sheet->getCell('F'.$row)->getValue();
            $noteRaw = $sheet->getCell('G'.$row)->getValue();

            if ($studentId === '' && $nisn === '') {
                continue;
            }

            $totalRows++;

            $student = null;
            if ($studentId !== '' && isset($enrolledStudents[$studentId])) {
                $student = $enrolledStudents[$studentId];
            } elseif ($nisn !== '' && isset($enrolledStudentsByNisn[$nisn])) {
                $student = $enrolledStudentsByNisn[$nisn];
            }

            if (! $student) {
                $errorList[] = "Baris {$row}: Siswa (ID: {$studentId}, NISN: {$nisn}) tidak terdaftar di kelas {$classroom->name}.";

                continue;
            }

            // Validasi Sakit, Izin, Alpa
            $sick = 0;
            if ($sickRaw !== null && $sickRaw !== '') {
                if (filter_var((string) $sickRaw, FILTER_VALIDATE_INT) === false || (int) $sickRaw < 0) {
                    $errorList[] = "Baris {$row}: Jumlah sakit ({$sickRaw}) tidak valid (harus bilangan bulat ≥ 0).";

                    continue;
                }
                $sick = (int) $sickRaw;
            }

            $permit = 0;
            if ($permitRaw !== null && $permitRaw !== '') {
                if (filter_var((string) $permitRaw, FILTER_VALIDATE_INT) === false || (int) $permitRaw < 0) {
                    $errorList[] = "Baris {$row}: Jumlah izin ({$permitRaw}) tidak valid (harus bilangan bulat ≥ 0).";

                    continue;
                }
                $permit = (int) $permitRaw;
            }

            $absent = 0;
            if ($absentRaw !== null && $absentRaw !== '') {
                if (filter_var((string) $absentRaw, FILTER_VALIDATE_INT) === false || (int) $absentRaw < 0) {
                    $errorList[] = "Baris {$row}: Jumlah alpa ({$absentRaw}) tidak valid (harus bilangan bulat ≥ 0).";

                    continue;
                }
                $absent = (int) $absentRaw;
            }

            $note = $noteRaw !== null ? trim((string) $noteRaw) : null;
            if ($note !== null && mb_strlen($note) > 500) {
                $errorList[] = "Baris {$row}: Catatan wali kelas melebihi 500 karakter.";

                continue;
            }

            $validRecords[] = [
                'period_id' => $period->id,
                'classroom_id' => $classroom->id,
                'student_id' => $student->id,
                'sick' => $sick,
                'permit' => $permit,
                'absent' => $absent,
                'note' => $note === '' ? null : $note,
            ];
        }

        if (empty($validRecords) && empty($errorList)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Tidak ada data kehadiran dan catatan pada file.'],
            ];
        }

        if ($totalRows > 0 && (count($errorList) / $totalRows) > 0.20) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => array_merge(
                    ['Lebih dari 20% data baris gagal diproses. Import dibatalkan demi integritas data.'],
                    $errorList
                ),
            ];
        }

        DB::transaction(function () use ($validRecords, $classroom) {
            foreach ($validRecords as $item) {
                HomeroomRecord::updateOrCreate(
                    [
                        'period_id' => $item['period_id'],
                        'student_id' => $item['student_id'],
                    ],
                    [
                        'classroom_id' => $item['classroom_id'],
                        'sick' => $item['sick'],
                        'permit' => $item['permit'],
                        'absent' => $item['absent'],
                        'note' => $item['note'],
                    ]
                );
            }

            ActivityLogger::log('import_homeroom', "Upload kehadiran & catatan kelas {$classroom->name}", $classroom);
        });

        return [
            'success' => true,
            'imported' => count($validRecords),
            'errors' => $errorList,
        ];
    }
}
