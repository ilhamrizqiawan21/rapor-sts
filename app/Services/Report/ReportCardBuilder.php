<?php

namespace App\Services\Report;

use App\Models\AchievementRecord;
use App\Models\Classroom;
use App\Models\ExtracurricularRecord;
use App\Models\HomeroomRecord;
use App\Models\MadrasahProfile;
use App\Models\Period;
use App\Models\PrintSetting;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Services\Grading\AchievementResolver;
use App\Services\Grading\ClassGradeSheet;
use App\Services\Grading\CompetencyDescriber;
use App\Services\Grading\GradeCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportCardBuilder
{
    public function __construct(
        protected GradeCalculator $calculator = new GradeCalculator,
        protected AchievementResolver $resolver = new AchievementResolver,
        protected CompetencyDescriber $describer = new CompetencyDescriber
    ) {}

    /**
     * Membangun payload lengkap rapor STS untuk semua siswa di kelas (atau satu siswa bila $targetStudent diisi).
     *
     * @return array{
     *     profile: MadrasahProfile,
     *     printSetting: PrintSetting,
     *     classroom: Classroom,
     *     period: Period,
     *     isLocked: bool,
     *     isDraft: bool,
     *     watermarkText: ?string,
     *     formattedDate: string,
     *     studentsData: Collection<int, array>
     * }
     */
    public function buildClassReportData(Classroom $classroom, Period $period, ?Student $targetStudent = null): array
    {
        $profile = MadrasahProfile::first() ?? new MadrasahProfile([
            'name' => 'Nama Madrasah',
            'address' => '-',
            'district' => '-',
            'regency' => '-',
            'province' => '-',
        ]);

        $printSetting = PrintSetting::firstOrNew(
            ['period_id' => $period->id],
            [
                'report_title' => 'LAPORAN HASIL SUMATIF TENGAH SEMESTER',
                'print_place' => $profile->regency ?? '-',
                'print_date' => now()->toDateString(),
                'score_label' => 'Nilai Akhir',
                'paper_size' => 'A4',
                'watermark_text' => null,
                'sections' => [
                    'show_competency' => true,
                    'show_attendance' => true,
                    'show_note' => true,
                    'show_parent_response' => true,
                    'show_extracurricular' => false,
                    'show_achievement' => false,
                    'show_total_score' => false,
                    'show_qr' => false,
                ],
            ]
        );

        $classroom->loadMissing([
            'academicYear',
            'homeroomTeacher',
            'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
        ]);

        $period->loadMissing('academicYear');

        $isLocked = $classroom->isLockedInPeriod($period->id);
        $isDraft = ! $isLocked;

        $watermarkText = $isDraft ? 'DRAFT' : ($printSetting->watermark_text ?: null);

        $formattedDate = Carbon::parse($printSetting->print_date)->translatedFormat('d F Y');

        // Ambil penugasan mengajar di kelas ini
        $assignments = TeachingAssignment::with([
            'subject.parent',
            'teacher',
            'period',
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
            'learningScopes.scores',
            'stsScores',
            'competencyOverrides',
        ])
            ->where('classroom_id', $classroom->id)
            ->where('period_id', $period->id)
            ->get();

        // Hitung grade sheet untuk setiap penugasan
        $assignmentGradeSheets = [];
        foreach ($assignments as $assignment) {
            $sheet = ClassGradeSheet::forAssignment($assignment, $this->calculator, $this->resolver, $this->describer);
            $assignmentGradeSheets[$assignment->id] = $sheet->rows->keyBy(fn ($r) => $r->student->id);
        }

        // Strukturkan mapel: Kelompokkan PAI dan sub-mapel, serta mapel mandiri
        $orderedSubjectGroups = $this->structureAssignments($assignments);

        // Ambil siswa aktif
        $enrolledStudents = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Ambil catatan & kehadiran wali kelas
        $homeroomRecords = HomeroomRecord::where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->get()
            ->keyBy('student_id');

        $extracurricularRecords = ExtracurricularRecord::query()
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->whereIn('student_id', $enrolledStudents->pluck('id'))
            ->orderBy('name')
            ->get()
            ->groupBy('student_id');

        $achievementRecords = AchievementRecord::query()
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->whereIn('student_id', $enrolledStudents->pluck('id'))
            ->orderBy('name')
            ->get()
            ->groupBy('student_id');

        $studentsData = collect();

        foreach ($enrolledStudents as $student) {
            $subjectRows = [];
            $totalScore = 0;
            $gradedCount = 0;
            $mainNumber = 1;

            foreach ($orderedSubjectGroups as $group) {
                if ($group['is_parent']) {
                    // Header mapel induk (tanpa nilai)
                    $subjectRows[] = [
                        'is_parent' => true,
                        'number' => (string) $mainNumber++,
                        'letter' => '',
                        'subject_name' => $group['name'],
                        'score' => null,
                        'formatted_score' => '',
                        'description' => '',
                        'is_below_kktp' => false,
                    ];

                    $childLetterIdx = 0;
                    foreach ($group['assignments'] as $childAssignment) {
                        $studentRow = $assignmentGradeSheets[$childAssignment->id][$student->id] ?? null;
                        $finalScore = $studentRow?->finalScore;
                        $isBelow = $finalScore !== null && $finalScore < $childAssignment->kktp;

                        if ($finalScore !== null) {
                            $totalScore += $finalScore;
                            $gradedCount++;
                        }

                        $letter = chr(65 + $childLetterIdx++).'.';

                        $subjectRows[] = [
                            'is_parent' => false,
                            'number' => '',
                            'letter' => $letter,
                            'subject_name' => $childAssignment->subject->name,
                            'score' => $finalScore,
                            'formatted_score' => $studentRow?->getFormattedFinalScore('–') ?? '–',
                            'description' => $studentRow?->description ?? '–',
                            'is_below_kktp' => $isBelow,
                        ];
                    }
                } else {
                    // Mapel mandiri
                    $assignment = $group['assignment'];
                    $studentRow = $assignmentGradeSheets[$assignment->id][$student->id] ?? null;
                    $finalScore = $studentRow?->finalScore;
                    $isBelow = $finalScore !== null && $finalScore < $assignment->kktp;

                    if ($finalScore !== null) {
                        $totalScore += $finalScore;
                        $gradedCount++;
                    }

                    $subjectRows[] = [
                        'is_parent' => false,
                        'number' => (string) $mainNumber++,
                        'letter' => '',
                        'subject_name' => $assignment->subject->name,
                        'score' => $finalScore,
                        'formatted_score' => $studentRow?->getFormattedFinalScore('–') ?? '–',
                        'description' => $studentRow?->description ?? '–',
                        'is_below_kktp' => $isBelow,
                    ];
                }
            }

            $homeroomRecord = $homeroomRecords->get($student->id);

            $averageScore = $gradedCount > 0 ? round($totalScore / $gradedCount, 1) : null;
            $formattedAverage = $averageScore !== null ? number_format($averageScore, 1, ',', '.') : '–';

            $studentsData->push([
                'student' => $student,
                'nis_nisn' => ($student->nis ? $student->nis.' / ' : '').$student->nisn,
                'subjectRows' => $subjectRows,
                'totalScore' => $gradedCount > 0 ? $totalScore : null,
                'averageScore' => $averageScore,
                'formattedAverage' => $formattedAverage,
                'gradedCount' => $gradedCount,
                'sick' => $homeroomRecord ? $homeroomRecord->sick : 0,
                'permit' => $homeroomRecord ? $homeroomRecord->permit : 0,
                'absent' => $homeroomRecord ? $homeroomRecord->absent : 0,
                'note' => $homeroomRecord?->note ?? '',
                'extracurriculars' => $extracurricularRecords->get($student->id, collect()),
                'achievements' => $achievementRecords->get($student->id, collect()),
            ]);
        }

        $sortedForRank = $studentsData
            ->filter(fn ($studentData) => $studentData['totalScore'] !== null)
            ->sortByDesc('totalScore')
            ->values();
        $ranksByStudentId = [];
        foreach ($sortedForRank as $rank => $studentData) {
            $ranksByStudentId[$studentData['student']->id] = $rank + 1;
        }

        $studentsData = $studentsData->map(function (array $studentData) use ($ranksByStudentId, $sortedForRank) {
            $studentData['rank'] = $ranksByStudentId[$studentData['student']->id] ?? null;
            $studentData['rank_total'] = $sortedForRank->count();

            return $studentData;
        });

        if ($targetStudent) {
            $studentsData = $studentsData
                ->filter(fn ($studentData) => $studentData['student']->id === $targetStudent->id)
                ->values();
        }

        return [
            'profile' => $profile,
            'printSetting' => $printSetting,
            'classroom' => $classroom,
            'period' => $period,
            'isLocked' => $isLocked,
            'isDraft' => $isDraft,
            'watermarkText' => $watermarkText,
            'formattedDate' => $formattedDate,
            'studentsData' => $studentsData,
        ];
    }

    /**
     * Membangun payload data leger kelas untuk tampilan cetak PDF dan Excel.
     *
     * @return array{
     *     profile: MadrasahProfile,
     *     printSetting: PrintSetting,
     *     classroom: Classroom,
     *     period: Period,
     *     defaultKktp: float,
     *     formattedDate: string,
     *     columnGroups: array<int, array>,
     *     subjectColumns: array<int, array{id: int, code: string, name: string, kktp: float}>,
     *     rows: Collection<int, array>,
     *     rowsPengetahuan: Collection<int, array>,
     *     rowsKeterampilan: Collection<int, array>,
     *     classAverages: array<int, string>,
     *     classAveragesPengetahuan: array<int, string>,
     *     classAveragesKeterampilan: array<int, string>,
     *     overallAverage: string,
     *     overallAveragePengetahuan: string,
     *     overallAverageKeterampilan: string
     * }
     */
    public function buildLedgerData(Classroom $classroom, Period $period): array
    {
        $profile = MadrasahProfile::first() ?? new MadrasahProfile;
        $printSetting = PrintSetting::firstOrNew(
            ['period_id' => $period->id],
            [
                'report_title' => 'LAPORAN HASIL SUMATIF TENGAH SEMESTER',
                'print_place' => $profile->regency ?? '-',
                'print_date' => now()->toDateString(),
                'score_label' => 'Nilai Akhir',
                'paper_size' => 'A4',
            ]
        );

        $classroom->loadMissing([
            'academicYear',
            'homeroomTeacher',
            'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
        ]);

        $assignments = TeachingAssignment::with([
            'subject.parent',
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
            'learningScopes.scores',
            'stsScores',
            'competencyOverrides',
        ])
            ->where('classroom_id', $classroom->id)
            ->where('period_id', $period->id)
            ->get();

        // Urutkan kolom mapel & kelompokkan
        $orderedColumns = [];
        $columnGroups = [];
        $structured = $this->structureAssignments($assignments);

        foreach ($structured as $group) {
            if ($group['is_parent']) {
                $subCols = [];
                foreach ($group['assignments'] as $a) {
                    $col = [
                        'id' => $a->id,
                        'code' => $a->subject->code,
                        'name' => $a->subject->name,
                        'kktp' => (float) $a->kktp,
                    ];
                    $orderedColumns[] = $col;
                    $subCols[] = $col;
                }
                $columnGroups[] = [
                    'is_group' => true,
                    'code' => $group['code'] ?? 'PAI',
                    'name' => $group['name'],
                    'columns' => $subCols,
                ];
            } else {
                $a = $group['assignment'];
                $col = [
                    'id' => $a->id,
                    'code' => $a->subject->code,
                    'name' => $a->subject->name,
                    'kktp' => (float) $a->kktp,
                ];
                $orderedColumns[] = $col;
                $columnGroups[] = [
                    'is_group' => false,
                    'id' => $a->id,
                    'code' => $a->subject->code,
                    'name' => $a->subject->name,
                    'kktp' => (float) $a->kktp,
                ];
            }
        }

        $assignmentGradeSheets = [];
        foreach ($assignments as $assignment) {
            $sheet = ClassGradeSheet::forAssignment($assignment, $this->calculator, $this->resolver, $this->describer);
            $assignmentGradeSheets[$assignment->id] = $sheet->rows->keyBy(fn ($r) => $r->student->id);
        }

        $enrolledStudents = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Hitung baris untuk Pengetahuan & Keterampilan
        $calcSection = function (string $type) use ($orderedColumns, $enrolledStudents, $assignmentGradeSheets) {
            $rows = collect();
            $subjectScoresList = [];
            foreach ($orderedColumns as $col) {
                $subjectScoresList[$col['id']] = [];
            }

            foreach ($enrolledStudents as $student) {
                $studentScores = [];
                $total = 0;
                $gradedCount = 0;

                foreach ($orderedColumns as $col) {
                    $assignmentId = $col['id'];
                    $sheetRow = $assignmentGradeSheets[$assignmentId][$student->id] ?? null;

                    if ($type === 'keterampilan') {
                        $score = $sheetRow?->summativeAverage ?? $sheetRow?->finalScore;
                    } else {
                        $score = $sheetRow?->finalScore ?? $sheetRow?->stsScore;
                    }

                    $studentScores[$assignmentId] = [
                        'score' => $score,
                        'formatted' => $score !== null ? (string) $score : '–',
                        'is_below' => ($score !== null && $score < $col['kktp']),
                    ];

                    if ($score !== null) {
                        $total += $score;
                        $gradedCount++;
                        $subjectScoresList[$assignmentId][] = $score;
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

            // Hitung rank
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

            // Hitung rata-rata per mapel & overall
            $classAverages = [];
            $totalAllScores = 0;
            $totalAllScoreCount = 0;

            foreach ($orderedColumns as $col) {
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

            $overallAvg = $totalAllScoreCount > 0
                ? number_format(round($totalAllScores / $totalAllScoreCount, 1), 1, ',', '.')
                : '–';

            return [
                'rows' => $rows,
                'classAverages' => $classAverages,
                'overallAverage' => $overallAvg,
            ];
        };

        $pengetahuanData = $calcSection('pengetahuan');
        $keterampilanData = $calcSection('keterampilan');

        $defaultKktp = (float) ($classroom->kktp ?? $assignments->first()?->kktp ?? 70);
        $formattedDate = Carbon::parse($printSetting->print_date ?? now())->translatedFormat('d F Y');

        return [
            'profile' => $profile,
            'printSetting' => $printSetting,
            'classroom' => $classroom,
            'period' => $period,
            'defaultKktp' => $defaultKktp,
            'formattedDate' => $formattedDate,
            'columnGroups' => $columnGroups,
            'subjectColumns' => $orderedColumns,
            'rows' => $pengetahuanData['rows'],
            'rowsPengetahuan' => $pengetahuanData['rows'],
            'rowsKeterampilan' => $keterampilanData['rows'],
            'classAverages' => $pengetahuanData['classAverages'],
            'classAveragesPengetahuan' => $pengetahuanData['classAverages'],
            'classAveragesKeterampilan' => $keterampilanData['classAverages'],
            'overallAverage' => $pengetahuanData['overallAverage'],
            'overallAveragePengetahuan' => $pengetahuanData['overallAverage'],
            'overallAverageKeterampilan' => $keterampilanData['overallAverage'],
        ];
    }

    /**
     * Mengelompokkan penugasan: Mapel Induk PAI (beserta sub-mapelnya), Mulok, dan Mapel mandiri.
     *
     * @param  Collection<int, TeachingAssignment>  $assignments
     * @return array<int, array>
     */
    protected function structureAssignments(Collection $assignments): array
    {
        $parentGroups = []; // [parent_id => ['is_parent' => true, 'code' => string, 'name' => string, 'sort_order' => int, 'assignments' => []]]
        $mulokGroup = [
            'is_parent' => true,
            'code' => 'MULOK',
            'name' => 'Muatan Lokal',
            'sort_order' => 900,
            'assignments' => [],
        ];
        $standalone = [];   // [['is_parent' => false, 'assignment' => TeachingAssignment, 'sort_order' => int]]

        foreach ($assignments as $a) {
            $subject = $a->subject;
            if ($subject->parent_id && $subject->parent) {
                $parent = $subject->parent;
                if (! isset($parentGroups[$parent->id])) {
                    $parentGroups[$parent->id] = [
                        'is_parent' => true,
                        'code' => $parent->code,
                        'name' => $parent->name,
                        'sort_order' => $parent->sort_order,
                        'assignments' => [],
                    ];
                }
                $parentGroups[$parent->id]['assignments'][] = $a;
            } elseif ($subject->group === 'mulok' || $subject->code === 'MULOK' || str_starts_with((string) $subject->code, 'MULOK')) {
                $mulokGroup['assignments'][] = $a;
            } else {
                $standalone[] = [
                    'is_parent' => false,
                    'assignment' => $a,
                    'sort_order' => $subject->sort_order,
                ];
            }
        }

        // Urutkan sub-mapel di setiap kelompok induk
        foreach ($parentGroups as &$pg) {
            usort($pg['assignments'], fn ($x, $y) => $x->subject->sort_order <=> $y->subject->sort_order);
        }
        unset($pg);

        if (! empty($mulokGroup['assignments'])) {
            usort($mulokGroup['assignments'], fn ($x, $y) => $x->subject->sort_order <=> $y->subject->sort_order);
        }

        $allGroups = array_values($parentGroups);
        if (! empty($mulokGroup['assignments'])) {
            $allGroups[] = $mulokGroup;
        }

        $merged = array_merge($allGroups, $standalone);
        usort($merged, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        return $merged;
    }
}
