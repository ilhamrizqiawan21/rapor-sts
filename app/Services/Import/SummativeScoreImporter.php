<?php

namespace App\Services\Import;

use App\Models\LearningScope;
use App\Models\SummativeScore;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SummativeScoreImporter
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

        $totalScoreRows = 0;
        $errorList = [];
        $sheetsData = [];

        foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
            $sheetTitle = trim($sheet->getTitle());

            // Ambil nomor urut dari nama sheet (contoh: LM1 -> 1)
            preg_match('/\d+/', $sheetTitle, $matches);
            $sequence = isset($matches[0]) ? (int) $matches[0] : ($sheetIndex + 1);

            $summary = trim((string) $sheet->getCell('B1')->getValue());
            $kktpRaw = $sheet->getCell('B2')->getValue();
            $kktp = ($kktpRaw !== null && is_numeric($kktpRaw) && (float) $kktpRaw > 0)
                ? (float) $kktpRaw
                : (float) $assignment->kktp;

            $highestRow = $sheet->getHighestDataRow();
            $studentScores = [];
            $hasAnyScoreInSheet = false;

            for ($row = 4; $row <= $highestRow; $row++) {
                $studentId = trim((string) $sheet->getCell('A'.$row)->getValue());
                $nisn = trim((string) $sheet->getCell('B'.$row)->getValue());
                $scoreRaw = $sheet->getCell('E'.$row)->getValue();

                if ($studentId === '' && $nisn === '') {
                    continue;
                }

                $totalScoreRows++;

                // Cari siswa
                $student = null;
                if ($studentId !== '' && isset($enrolledStudents[$studentId])) {
                    $student = $enrolledStudents[$studentId];
                } elseif ($nisn !== '' && isset($enrolledStudentsByNisn[$nisn])) {
                    $student = $enrolledStudentsByNisn[$nisn];
                }

                if (! $student) {
                    $errorList[] = "Sheet {$sheetTitle} Baris {$row}: Siswa (ID: {$studentId}, NISN: {$nisn}) tidak terdaftar di kelas {$assignment->classroom->name}.";

                    continue;
                }

                // Cek nilai
                $score = null;
                if ($scoreRaw !== null && $scoreRaw !== '') {
                    if (! is_numeric($scoreRaw) || (float) $scoreRaw < 0 || (float) $scoreRaw > 100) {
                        $errorList[] = "Sheet {$sheetTitle} Baris {$row}: Nilai siswa {$student->name} ({$scoreRaw}) tidak valid (harus angka 0–100).";

                        continue;
                    }
                    $score = (float) $scoreRaw;
                    $hasAnyScoreInSheet = true;
                }

                $studentScores[] = [
                    'student_id' => $student->id,
                    'score' => $score,
                ];
            }

            // Jika sheet tidak memiliki ringkasan dan tidak ada nilai, lewati sheet ini
            if ($summary === '' && ! $hasAnyScoreInSheet) {
                continue;
            }

            if ($summary === '' && $hasAnyScoreInSheet) {
                $errorList[] = "Sheet {$sheetTitle}: Ringkasan LM pada sel B1 wajib diisi.";

                continue;
            }

            $sheetsData[] = [
                'sequence' => $sequence,
                'summary' => $summary,
                'kktp' => $kktp,
                'scores' => $studentScores,
            ];
        }

        if (empty($sheetsData)) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => ['Tidak ada data sheet LM yang valid untuk diimpor.'],
            ];
        }

        // Aturan: Jika > 20% baris gagal, batalkan seluruh file
        if ($totalScoreRows > 0 && (count($errorList) / $totalScoreRows) > 0.20) {
            return [
                'success' => false,
                'imported' => 0,
                'errors' => array_merge(
                    ['Gagal: Tingkat kesalahan lebih dari 20% ('.count($errorList)." dari {$totalScoreRows} data gagal). Proses dibatalkan demi keamanan data."],
                    array_slice($errorList, 0, 10)
                ),
            ];
        }

        $importedScoreCount = 0;

        DB::transaction(function () use ($sheetsData, $assignment, &$importedScoreCount) {
            foreach ($sheetsData as $data) {
                $scope = LearningScope::updateOrCreate(
                    [
                        'teaching_assignment_id' => $assignment->id,
                        'sequence' => $data['sequence'],
                    ],
                    [
                        'summary' => $data['summary'],
                        'kktp' => $data['kktp'],
                    ]
                );

                foreach ($data['scores'] as $sc) {
                    SummativeScore::updateOrCreate(
                        [
                            'learning_scope_id' => $scope->id,
                            'student_id' => $sc['student_id'],
                        ],
                        [
                            'score' => $sc['score'],
                        ]
                    );
                    $importedScoreCount++;
                }
            }
        });

        ActivityLogger::log('import_summative_scores', "Mengimpor nilai sumatif LM untuk mapel {$assignment->subject->name} kelas {$assignment->classroom->name}", $assignment);

        return [
            'success' => true,
            'imported' => $importedScoreCount,
            'errors' => $errorList,
        ];
    }
}
