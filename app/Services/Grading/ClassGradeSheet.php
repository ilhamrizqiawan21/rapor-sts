<?php

namespace App\Services\Grading;

use App\Enums\Achievement;
use App\Models\LearningScope;
use App\Models\Student;
use App\Models\TeachingAssignment;
use Illuminate\Support\Collection;

class ClassGradeSheet
{
    /**
     * @param  Collection<int, LearningScope>  $learningScopes
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, StudentGradeRow>  $rows
     * @param  array<string, int>  $achievementCounts
     */
    public function __construct(
        public readonly TeachingAssignment $teachingAssignment,
        public readonly Collection $learningScopes,
        public readonly Collection $students,
        public readonly Collection $rows,
        public readonly int $totalStudents,
        public readonly int $completeCount,
        public readonly int $incompleteCount,
        public readonly bool $isAllComplete,
        public readonly bool $hasEmptySummativeCells,
        public readonly ?float $classAverage,
        public readonly string $formattedClassAverage,
        public readonly ?int $highestScore,
        public readonly ?int $lowestScore,
        public readonly array $achievementCounts,
    ) {}

    /**
     * Factory untuk mengkalkulasi dan membuat sheet nilai kelas dari TeachingAssignment.
     */
    public static function forAssignment(
        TeachingAssignment $assignment,
        ?GradeCalculator $calculator = null,
        ?AchievementResolver $resolver = null,
        ?CompetencyDescriber $describer = null
    ): self {
        $calculator ??= new GradeCalculator;
        $resolver ??= new AchievementResolver;
        $describer ??= new CompetencyDescriber;

        // Eager load relasi yang diperlukan bila belum ter-load
        $assignment->loadMissing([
            'period',
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $assignment->period->academic_year_id)->with('student'),
            'learningScopes' => fn ($q) => $q->orderBy('sequence')->with('scores'),
            'stsScores',
            'competencyOverrides',
        ]);

        $learningScopes = $assignment->learningScopes;

        // Ambil siswa aktif di kelas ini pada tahun pelajaran assignment
        $students = $assignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $wS = (float) $assignment->weight_summative;
        $wT = (float) $assignment->weight_sts;
        $mapelKktp = (float) $assignment->kktp;

        $rows = collect();
        $hasEmptyCells = false;

        $completedFinalScores = [];
        $achievementCounts = [
            Achievement::BelumTercapai->value => 0,
            Achievement::Cukup->value => 0,
            Achievement::Baik->value => 0,
            Achievement::SangatBaik->value => 0,
        ];

        // Indeks skor untuk akses cepat O(1)
        $stsScoresByStudent = $assignment->stsScores->keyBy('student_id');
        $overridesByStudent = $assignment->competencyOverrides->keyBy('student_id');

        // Indeks skor sumatif: [scope_id => [student_id => score]]
        $summativeScoresGrouped = [];
        foreach ($learningScopes as $scope) {
            $summativeScoresGrouped[$scope->id] = $scope->scores->keyBy('student_id');
        }

        foreach ($students as $student) {
            $studentSummativeScores = [];
            $scopesWithScores = [];

            foreach ($learningScopes as $scope) {
                $scoreModel = $summativeScoresGrouped[$scope->id][$student->id] ?? null;
                $scoreVal = $scoreModel?->score !== null ? (float) $scoreModel->score : null;

                $studentSummativeScores[$scope->id] = $scoreVal;

                if ($scoreVal === null) {
                    $hasEmptyCells = true;
                }

                $scopesWithScores[] = [
                    'id' => $scope->id,
                    'sequence' => $scope->sequence,
                    'summary' => $scope->summary,
                    'kktp' => (float) $scope->kktp,
                    'score' => $scoreVal,
                ];
            }

            // Hitung rerata sumatif
            $summativeAvgRaw = $calculator->calculateSummativeAverage($studentSummativeScores);
            $summativeAvgRounded = $calculator->roundHalfUp($summativeAvgRaw);

            // Ambil skor STS
            $stsModel = $stsScoresByStudent->get($student->id);
            $stsVal = $stsModel?->score !== null ? (float) $stsModel->score : null;

            // Hitung nilai akhir rapor
            $finalScoreRaw = $calculator->calculateFinalScore($summativeAvgRaw, $stsVal, $wS, $wT);
            $finalScoreRounded = $calculator->roundHalfUp($finalScoreRaw);

            // Predikat & Deskripsi
            $achievement = $resolver->resolve($finalScoreRounded, $mapelKktp);

            $overrideModel = $overridesByStudent->get($student->id);
            $overrideText = $overrideModel?->description;
            $isOverridden = ! empty($overrideText);

            $description = $describer->describe($achievement, $scopesWithScores, $overrideText);
            $statusLabel = $calculator->determineStatusLabel($summativeAvgRaw, $stsVal, $wT);
            $isComplete = $finalScoreRounded !== null;

            if ($isComplete) {
                $completedFinalScores[] = $finalScoreRounded;
                if ($achievement) {
                    $achievementCounts[$achievement->value]++;
                }
            }

            $rows->push(new StudentGradeRow(
                student: $student,
                summativeScores: $studentSummativeScores,
                summativeAverageRaw: $summativeAvgRaw,
                summativeAverage: $summativeAvgRounded,
                stsScore: $stsVal,
                finalScoreRaw: $finalScoreRaw,
                finalScore: $finalScoreRounded,
                achievement: $achievement,
                description: $description,
                isDescriptionOverridden: $isOverridden,
                statusLabel: $statusLabel,
                isComplete: $isComplete,
            ));
        }

        $totalStudents = $students->count();
        $completeCount = count($completedFinalScores);
        $incompleteCount = $totalStudents - $completeCount;
        $isAllComplete = ($totalStudents > 0 && $incompleteCount === 0);

        $classAverage = $completeCount > 0 ? (array_sum($completedFinalScores) / $completeCount) : null;
        $formattedClassAverage = $calculator->formatAverage($classAverage);

        $highestScore = $completeCount > 0 ? max($completedFinalScores) : null;
        $lowestScore = $completeCount > 0 ? min($completedFinalScores) : null;

        return new self(
            teachingAssignment: $assignment,
            learningScopes: $learningScopes,
            students: $students,
            rows: $rows,
            totalStudents: $totalStudents,
            completeCount: $completeCount,
            incompleteCount: $incompleteCount,
            isAllComplete: $isAllComplete,
            hasEmptySummativeCells: $hasEmptyCells,
            classAverage: $classAverage,
            formattedClassAverage: $formattedClassAverage,
            highestScore: $highestScore,
            lowestScore: $lowestScore,
            achievementCounts: $achievementCounts,
        );
    }
}
