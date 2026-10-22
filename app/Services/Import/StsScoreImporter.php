<?php

namespace App\Services\Import;

use App\Models\StsScore;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StsScoreImporter
{
    /**
     * @return array{success: bool, imported: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file, TeachingAssignment $assignment): array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (Exception $e) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['File tidak dapat dibaca: '.$e->getMessage()],
            ];
        }

        $assignment->loadMissing([
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $assignment->period->academic_year_id)->with('student'),
            'subject',
        ]);

        $enrolledStudents = $assignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->keyBy('id');

        $enrolledStudentsByNisn = $enrolledStudents->keyBy('nisn');

        // Cari sheet STS atau sheet pertama
        $sheet = $spreadsheet->getSheetByName('STS') ?? $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $totalRows = 0;
        $errorList = [];
        $validScores = [];

        for ($row = 4; $row <= $highestRow; $row++) {
            $studentId = trim((string) $sheet->getCell('A'.$row)->getValue());
            $nisn = trim((string) $sheet->getCell('B'.$row)->getValue());
            $scoreRaw = $sheet->getCell('E'.$row)->getValue();

            if ($studentId === '' && $nisn === '') {
                continue;
            }

            $totalRows++;

            // Cari siswa
            $student = null;
            if ($studentId !== '' && isset($enrolledStudents[$studentId])) {
                $student = $enrolledStudents[$studentId];
            } elseif ($nisn !== '' && isset($enrolledStudentsByNisn[$nisn])) {
                $student = $enrolledStudentsByNisn[$nisn];
            }

            if (! $student) {
                $errorList[] = "Baris {$row}: Siswa (ID: {$studentId}, NISN: {$nisn}) tidak terdaftar di kelas {$assignment->classroom->name}.";

                continue;
            }

            $score = null;
            if ($scoreRaw !== null && $scoreRaw !== '') {
                if (! is_numeric($scoreRaw) || (float) $scoreRaw < 0 || (float) $scoreRaw > 100) {
                    $errorList[] = "Baris {$row}: Nilai STS siswa {$student->name} ({$scoreRaw}) tidak valid (harus angka 0–100).";

                    continue;
                }
                $score = (float) $scoreRaw;
            }

            $validScores[] = [
                'student_id' => $student->id,
                'score' => $score,
            ];
        }

        if (empty($validScores) && empty($errorList)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Tidak ada data nilai STS pada file template.'],
            ];
        }

        // Aturan: Jika > 20% baris gagal, batalkan seluruh file
        if ($totalRows > 0 && (count($errorList) / $totalRows) > 0.20) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => array_merge(
                    ['Gagal: Tingkat kesalahan lebih dari 20% ('.count($errorList)." dari {$totalRows} baris gagal). Proses dibatalkan demi keamanan data."],
                    array_slice($errorList, 0, 10)
                ),
            ];
        }

        $importedCount = 0;

        DB::transaction(function () use ($validScores, $assignment, &$importedCount) {
            foreach ($validScores as $item) {
                StsScore::updateOrCreate(
                    [
                        'teaching_assignment_id' => $assignment->id,
                        'student_id' => $item['student_id'],
                    ],
                    [
                        'period_id' => $assignment->period_id,
                        'score' => $item['score'],
                    ]
                );
                $importedCount++;
            }
        });

        ActivityLogger::log('import_sts_scores', "Mengimpor nilai STS untuk mapel {$assignment->subject->name} kelas {$assignment->classroom->name}", $assignment);

        return [
            'success' => true,
            'imported' => $importedCount,
            'errors' => $errorList,
        ];
    }
}

