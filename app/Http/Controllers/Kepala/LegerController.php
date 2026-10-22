<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\TeachingAssignment;
use App\Services\Grading\AchievementResolver;
use App\Services\Grading\ClassGradeSheet;
use App\Services\Grading\CompetencyDescriber;
use App\Services\Grading\GradeCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegerController extends Controller
{
    public function __construct(
        protected GradeCalculator $calculator = new GradeCalculator,
        protected AchievementResolver $resolver = new AchievementResolver,
        protected CompetencyDescriber $describer = new CompetencyDescriber
    ) {}

    public function index(Request $request): View
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $classrooms = Classroom::where('academic_year_id', $period->academic_year_id)
            ->with(['homeroomTeacher'])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $selectedLevel = $request->query('tingkat');
        if ($selectedLevel !== null && $selectedLevel !== '') {
            $filteredClassrooms = $classrooms->where('level', (int) $selectedLevel)->values();
        } else {
            $filteredClassrooms = $classrooms;
        }

        $selectedClassroomId = $request->query('classroom_id', $filteredClassrooms->first()?->id);
        $selectedClassroom = $filteredClassrooms->firstWhere('id', (int) $selectedClassroomId) ?? $filteredClassrooms->first();

        $viewType = $request->query('tampilkan', 'rapor'); // rapor, sumatif, sts

        $subjectColumns = [];
        $rows = collect();
        $classAverages = [];
        $overallAverage = '–';
        $submittedCount = 0;
        $totalAssignments = 0;

        if ($selectedClassroom) {
            $selectedClassroom->loadMissing([
                'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
            ]);

            $assignments = TeachingAssignment::where('classroom_id', $selectedClassroom->id)
                ->where('period_id', $period->id)
                ->with([
                    'subject.parent',
                    'teacher',
                    'learningScopes.scores',
                    'stsScores',
                    'competencyOverrides',
                ])
                ->get();

            $totalAssignments = $assignments->count();
            $submittedCount = $assignments->filter(fn ($a) => $a->isSubmitted())->count();

            // Urutkan kolom mapel
            $structuredAssignments = $this->structureAssignments($assignments);
            foreach ($structuredAssignments as $group) {
                if ($group['is_parent']) {
                    foreach ($group['assignments'] as $a) {
                        $subjectColumns[] = [
                            'id' => $a->id,
                            'code' => $a->subject->code,
                            'name' => $a->subject->name,
                            'kktp' => (float) $a->kktp,
                            'is_submitted' => $a->isSubmitted(),
                        ];
                    }
                } else {
                    $a = $group['assignment'];
                    $subjectColumns[] = [
                        'id' => $a->id,
                        'code' => $a->subject->code,
                        'name' => $a->subject->name,
                        'kktp' => (float) $a->kktp,
                        'is_submitted' => $a->isSubmitted(),
                    ];
                }
            }

            // Hitung nilai per penugasan
            $assignmentGradeSheets = [];
            foreach ($assignments as $a) {
                $sheet = ClassGradeSheet::forAssignment($a, $this->calculator, $this->resolver, $this->describer);
                $assignmentGradeSheets[$a->id] = $sheet->rows->keyBy(fn ($r) => $r->student->id);
            }

            $enrolledStudents = $selectedClassroom->enrollments
                ->pluck('student')
                ->filter(fn ($s) => $s !== null && $s->is_active)
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $subjectScoresList = [];
            foreach ($subjectColumns as $col) {
                $subjectScoresList[$col['id']] = [];
            }

            foreach ($enrolledStudents as $student) {
                $studentScores = [];
                $total = 0;
                $gradedCount = 0;

                foreach ($subjectColumns as $col) {
                    $assignmentId = $col['id'];
                    $sheetRow = $assignmentGradeSheets[$assignmentId][$student->id] ?? null;

                    // Nilai tergantung viewType
                    $rawScore = null;
                    if ($col['is_submitted']) {
                        if ($viewType === 'sumatif') {
                            $rawScore = $sheetRow?->summativeAverage;
                        } elseif ($viewType === 'sts') {
                            $rawScore = $sheetRow?->stsScore !== null ? (int) round((float) $sheetRow->stsScore) : null;
                        } else {
                            $rawScore = $sheetRow?->finalScore;
                        }
                    }

                    $isBelow = ($rawScore !== null && $rawScore < $col['kktp']);

                    $studentScores[$assignmentId] = [
                        'score' => $rawScore,
                        'formatted' => $rawScore !== null ? (string) $rawScore : '–',
                        'is_below' => $isBelow,
                    ];

                    if ($rawScore !== null) {
                        $total += $rawScore;
                        $gradedCount++;
                        $subjectScoresList[$assignmentId][] = $rawScore;
                    }
                }

                $avg = $gradedCount > 0 ? round($total / $gradedCount, 1) : null;

                $rows->push([
                    'student' => $student,
                    'scores' => $studentScores,
                    'total' => $gradedCount > 0 ? $total : 0,
                    'average' => $avg,
                    'formatted_average' => $avg !== null ? number_format($avg, 1, ',', '.') : '–',
                    'rank' => 0,
                ]);
            }

            // Peringkat
            $sortedForRank = $rows->sortByDesc('total')->values();
            $ranksByStudentId = [];
            $currentRank = 1;
            foreach ($sortedForRank as $r) {
                $ranksByStudentId[$r['student']->id] = $currentRank++;
            }

            $rows = $rows->map(function ($r) use ($ranksByStudentId) {
                $r['rank'] = $ranksByStudentId[$r['student']->id] ?? 0;

                return $r;
            });

            // Rata-rata per kolom mapel
            $totalAllScores = 0;
            $totalAllScoreCount = 0;

            foreach ($subjectColumns as $col) {
                $scores = $subjectScoresList[$col['id']];
                if (count($scores) > 0) {
                    $avg = round(array_sum($scores) / count($scores), 1);
                    $classAverages[$col['id']] = number_format($avg, 1, ',', '.');
                    $totalAllScores += array_sum($scores);
                    $totalAllScoreCount += count($scores);
                } else {
                    $classAverages[$col['id']] = '–';
                }
            }

            $overallAverage = $totalAllScoreCount > 0
                ? number_format(round($totalAllScores / $totalAllScoreCount, 1), 1, ',', '.')
                : '–';
        }

        $levels = $classrooms->pluck('level')->unique()->sort()->values();

        return view('kepala.leger', [
            'classrooms' => $filteredClassrooms,
            'allClassrooms' => $classrooms,
            'levels' => $levels,
            'selectedLevel' => $selectedLevel,
            'selectedClassroom' => $selectedClassroom,
            'viewType' => $viewType,
            'subjectColumns' => $subjectColumns,
            'rows' => $rows,
            'classAverages' => $classAverages,
            'overallAverage' => $overallAverage,
            'submittedCount' => $submittedCount,
            'totalAssignments' => $totalAssignments,
            'period' => $period,
        ]);
    }

    /**
     * @param  Collection<int, TeachingAssignment>  $assignments
     * @return array<int, array>
     */
    protected function structureAssignments($assignments): array
    {
        $parentGroups = [];
        $standalone = [];

        foreach ($assignments as $a) {
            $subj = $a->subject;
            if ($subj->parent_id && $subj->parent) {
                $parent = $subj->parent;
                if (! isset($parentGroups[$parent->id])) {
                    $parentGroups[$parent->id] = [
                        'is_parent' => true,
                        'name' => $parent->name,
                        'sort_order' => $parent->sort_order,
                        'assignments' => [],
                    ];
                }
                $parentGroups[$parent->id]['assignments'][] = $a;
            } else {
                $standalone[] = [
                    'is_parent' => false,
                    'assignment' => $a,
                    'sort_order' => $subj->sort_order,
                ];
            }
        }

        foreach ($parentGroups as &$pg) {
            usort($pg['assignments'], fn ($x, $y) => $x->subject->sort_order <=> $y->subject->sort_order);
        }
        unset($pg);

        $merged = array_merge(array_values($parentGroups), $standalone);
        usort($merged, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $merged;
    }
}
