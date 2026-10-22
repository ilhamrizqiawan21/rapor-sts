<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\HomeroomRecord;
use App\Models\LearningScope;
use App\Models\Period;
use App\Models\StsScore;
use App\Models\SummativeScore;
use App\Models\TeachingAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Nilai demo: VII.A lengkap & terkirim (siap dikunci wali kelas),
 * VII.B sebagian terkirim (untuk demo pemantauan status).
 * Deterministik: dijalankan ulang menghasilkan angka yang sama.
 */
class DemoScoresSeeder extends Seeder
{
    private const SCOPE_SUMMARIES = [
        'memahami dan menerapkan konsep dasar materi',
        'menganalisis dan menyajikan hasil pembelajaran',
        'mempraktikkan keterampilan sesuai materi',
    ];

    public function run(): void
    {
        $period = Period::where('is_active', true)->firstOrFail();

        DB::transaction(function () use ($period) {
            foreach (['VII.A' => null, 'VII.B' => 0.5] as $className => $submitRatio) {
                $classroom = Classroom::where('name', $className)->firstOrFail();
                $studentIds = Enrollment::where('classroom_id', $classroom->id)->pluck('student_id');

                $assignments = TeachingAssignment::where('period_id', $period->id)
                    ->where('classroom_id', $classroom->id)
                    ->orderBy('id')
                    ->get();

                foreach ($assignments as $i => $assignment) {
                    $this->seedAssignment($assignment, $studentIds, $assignment->id);

                    $submitted = $submitRatio === null || $i < (int) ceil($assignments->count() * $submitRatio);
                    if ($submitted) {
                        $assignment->update([
                            'submitted_at' => now(),
                            'submitted_by' => $assignment->teacher_id,
                        ]);
                    }
                }

                foreach ($studentIds as $n => $studentId) {
                    HomeroomRecord::updateOrCreate(
                        ['period_id' => $period->id, 'student_id' => $studentId],
                        [
                            'classroom_id' => $classroom->id,
                            'sick' => $n % 3,
                            'permit' => $n % 2,
                            'absent' => $n % 5 === 0 ? 1 : 0,
                            'note' => $n % 4 === 0
                                ? 'Pertahankan prestasi dan tingkatkan kedisiplinan belajar.'
                                : 'Terus semangat belajar dan aktif dalam kegiatan kelas.',
                        ]
                    );
                }
            }
        });
    }

    private function seedAssignment(TeachingAssignment $assignment, $studentIds, int $seed): void
    {
        foreach (range(1, 3) as $seq) {
            $scope = LearningScope::updateOrCreate(
                ['teaching_assignment_id' => $assignment->id, 'sequence' => $seq],
                ['summary' => self::SCOPE_SUMMARIES[$seq - 1], 'kktp' => $assignment->kktp]
            );

            foreach ($studentIds as $studentId) {
                SummativeScore::updateOrCreate(
                    ['learning_scope_id' => $scope->id, 'student_id' => $studentId],
                    ['score' => $this->score($seed, $seq, $studentId)]
                );
            }
        }

        foreach ($studentIds as $studentId) {
            StsScore::updateOrCreate(
                ['teaching_assignment_id' => $assignment->id, 'student_id' => $studentId],
                ['score' => $this->score($seed, 9, $studentId)]
            );
        }
    }

    /** Nilai 62–98, sebaran cukup agar keempat predikat muncul. */
    private function score(int $a, int $b, int $c): int
    {
        return 62 + (crc32("$a-$b-$c") % 37);
    }
}
