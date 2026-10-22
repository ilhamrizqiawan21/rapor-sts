<?php

namespace App\Services\Headmaster;

use App\Enums\Achievement;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\TeachingAssignment;
use App\Services\Grading\AchievementResolver;
use App\Services\Grading\ClassGradeSheet;
use App\Services\Grading\CompetencyDescriber;
use App\Services\Grading\GradeCalculator;

class DashboardAnalytics
{
    public function __construct(
        protected GradeCalculator $calculator = new GradeCalculator,
        protected AchievementResolver $resolver = new AchievementResolver,
        protected CompetencyDescriber $describer = new CompetencyDescriber
    ) {}

    /**
     * Hitung metrik pemantauan kepala madrasah dengan eager loading optimal bebas N+1.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(Period $period, ?int $filterLevel = null): array
    {
        // 1. Ambil semua kelas di tahun pelajaran aktif
        $allClassrooms = Classroom::where('academic_year_id', $period->academic_year_id)
            ->with([
                'homeroomTeacher',
                'locks' => fn ($q) => $q->where('period_id', $period->id),
                'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
            ])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $availableLevels = $allClassrooms->pluck('level')->unique()->sort()->values();

        $filteredClassrooms = ($filterLevel !== null)
            ? $allClassrooms->where('level', $filterLevel)->values()
            : $allClassrooms;

        $filteredClassroomIds = $filteredClassrooms->pluck('id');

        // 2. Ambil semua penugasan mengajar periode aktif
        $allAssignments = TeachingAssignment::where('period_id', $period->id)
            ->with([
                'period',
                'subject.parent',
                'teacher',
                'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
                'learningScopes.scores',
                'stsScores',
                'competencyOverrides',
            ])
            ->get();

        $assignments = $allAssignments->whereIn('classroom_id', $filteredClassroomIds)->values();

        $totalAssignments = $assignments->count();
        $submittedAssignments = $assignments->filter(fn ($a) => $a->isSubmitted());
        $submittedCount = $submittedAssignments->count();

        $percentSubmitted = $totalAssignments > 0
            ? (int) round(($submittedCount / $totalAssignments) * 100)
            : 0;

        $unsubmittedAssignments = $assignments->filter(fn ($a) => ! $a->isSubmitted());
        $unsubmittedTeacherIds = $unsubmittedAssignments->pluck('teacher_id')->unique();
        $unsubmittedTeacherCount = $unsubmittedTeacherIds->count();

        $totalTeacherIds = $assignments->pluck('teacher_id')->unique()->count();

        // Status Kelas Dikunci
        $totalClassrooms = $filteredClassrooms->count();
        $lockedClassroomsCount = $filteredClassrooms->filter(fn ($c) => $c->locks->isNotEmpty())->count();

        // 3. Hitung lembar nilai untuk tiap penugasan
        $assignmentGradeSheets = [];
        $allStudentScores = []; // kumpulan nilai untuk rata-rata madrasah

        foreach ($allAssignments as $a) {
            $sheet = ClassGradeSheet::forAssignment($a, $this->calculator, $this->resolver, $this->describer);
            $assignmentGradeSheets[$a->id] = $sheet;

            // Masukkan ke rata-rata jika masuk filter
            if ($filteredClassroomIds->contains($a->classroom_id)) {
                foreach ($sheet->rows as $row) {
                    if ($row->finalScore !== null) {
                        $allStudentScores[] = $row->finalScore;
                    }
                }
            }
        }

        // Rata-rata nilai STS madrasah
        $overallAverage = count($allStudentScores) > 0
            ? round(array_sum($allStudentScores) / count($allStudentScores), 1)
            : null;
        $formattedOverallAverage = $overallAverage !== null
            ? number_format($overallAverage, 1, ',', '.')
            : '–';

        // 4. Tabel Progres per Kelas (FR-KPL-02)
        $classProgress = [];
        foreach ($filteredClassrooms as $c) {
            $classAssignments = $assignments->where('classroom_id', $c->id);
            $totalInClass = $classAssignments->count();
            $submittedInClass = $classAssignments->filter(fn ($a) => $a->isSubmitted())->count();
            $progressPercent = $totalInClass > 0
                ? (int) round(($submittedInClass / $totalInClass) * 100)
                : 0;

            $isLocked = $c->locks->isNotEmpty();

            // Nilai siswa di kelas ini
            $classScores = [];
            $studentsBelowKktp = [];

            foreach ($classAssignments as $a) {
                $sheet = $assignmentGradeSheets[$a->id] ?? null;
                if (! $sheet) {
                    continue;
                }

                foreach ($sheet->rows as $row) {
                    if ($row->finalScore !== null) {
                        $classScores[] = $row->finalScore;
                        if ($row->finalScore < $a->kktp) {
                            $studentsBelowKktp[$row->student->id] = true;
                        }
                    }
                }
            }

            $classAvg = count($classScores) > 0
                ? round(array_sum($classScores) / count($classScores), 1)
                : null;

            $classProgress[] = [
                'classroom' => $c,
                'homeroom_teacher' => $c->homeroomTeacher?->name ?? 'Belum ditentukan',
                'submitted_count' => $submittedInClass,
                'total_count' => $totalInClass,
                'progress_percent' => $progressPercent,
                'is_locked' => $isLocked,
                'class_average' => $classAvg !== null ? number_format($classAvg, 1, ',', '.') : '–',
                'students_below_kktp_count' => count($studentsBelowKktp),
            ];
        }

        // 5. Daftar Guru Belum Mengirim (FR-KPL-03)
        $unsubmittedTeachers = [];
        $unsubmittedGrouped = $unsubmittedAssignments->groupBy('teacher_id');

        foreach ($unsubmittedGrouped as $teacherId => $teacherAssignments) {
            $teacher = $teacherAssignments->first()->teacher;
            if (! $teacher) {
                continue;
            }

            foreach ($teacherAssignments as $a) {
                $studentCount = $a->classroom->enrollments
                    ->pluck('student')
                    ->filter(fn ($s) => $s !== null && $s->is_active)
                    ->count();

                $lmCount = $a->learningScopes->count();
                $hasMissingLm = false;

                if ($lmCount === 0) {
                    $hasMissingLm = true;
                } elseif ($studentCount > 0) {
                    $scoredStudentCount = $a->learningScopes
                        ->flatMap(fn ($ls) => $ls->scores->pluck('student_id'))
                        ->unique()
                        ->count();
                    if ($scoredStudentCount < $studentCount) {
                        $hasMissingLm = true;
                    }
                }

                $hasMissingSts = false;
                if ((float) $a->weight_sts > 0) {
                    $stsFilledCount = $a->stsScores->filter(fn ($s) => $s->score !== null)->count();
                    if ($stsFilledCount < $studentCount) {
                        $hasMissingSts = true;
                    }
                }

                $missingParts = [];
                if ($hasMissingLm) {
                    $missingParts[] = 'Sumatif LM';
                }
                if ($hasMissingSts) {
                    $missingParts[] = 'Nilai STS';
                }
                if (empty($missingParts)) {
                    $missingParts[] = 'Belum kirim';
                }

                $unsubmittedTeachers[] = [
                    'teacher_name' => $teacher->name,
                    'subject_name' => $a->subject->name,
                    'classroom_name' => $a->classroom->name,
                    'missing_label' => implode(', ', $missingParts),
                ];
            }
        }

        // 6. Sebaran Ketercapaian per Tingkat (FR-KPL-04)
        // Dihitung langsung dari $assignmentGradeSheets tanpa query tambahan
        $achievementDistribution = [];

        foreach ($availableLevels as $lvl) {
            $levelClassrooms = $allClassrooms->where('level', $lvl)->pluck('id');
            $levelAssignments = $allAssignments->whereIn('classroom_id', $levelClassrooms);

            $counts = [
                Achievement::SangatBaik->value => 0,
                Achievement::Baik->value => 0,
                Achievement::Cukup->value => 0,
                Achievement::BelumTercapai->value => 0,
            ];

            $totalScoresInLevel = 0;

            foreach ($levelAssignments as $a) {
                $sheet = $assignmentGradeSheets[$a->id] ?? null;
                if (! $sheet) {
                    continue;
                }
                foreach ($sheet->rows as $r) {
                    if ($r->achievement !== null) {
                        $counts[$r->achievement->value]++;
                        $totalScoresInLevel++;
                    }
                }
            }

            $percentages = [];
            foreach ($counts as $key => $count) {
                $percentages[$key] = $totalScoresInLevel > 0
                    ? (int) round(($count / $totalScoresInLevel) * 100)
                    : 0;
            }

            $levelRoman = match ($lvl) {
                7 => 'VII',
                8 => 'VIII',
                9 => 'IX',
                default => (string) $lvl,
            };

            $achievementDistribution[] = [
                'level' => $lvl,
                'level_roman' => $levelRoman,
                'total_scores' => $totalScoresInLevel,
                'counts' => $counts,
                'percentages' => $percentages,
            ];
        }

        return [
            'period' => $period,
            'filterLevel' => $filterLevel,
            'availableLevels' => $availableLevels,
            'percentSubmitted' => $percentSubmitted,
            'submittedCount' => $submittedCount,
            'totalAssignments' => $totalAssignments,
            'unsubmittedTeacherCount' => $unsubmittedTeacherCount,
            'totalTeacherCount' => $totalTeacherIds,
            'lockedClassroomsCount' => $lockedClassroomsCount,
            'totalClassrooms' => $totalClassrooms,
            'overallAverage' => $formattedOverallAverage,
            'classProgress' => $classProgress,
            'unsubmittedTeachers' => $unsubmittedTeachers,
            'achievementDistribution' => $achievementDistribution,
        ];
    }
}
