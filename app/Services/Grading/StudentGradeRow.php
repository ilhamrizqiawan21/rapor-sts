<?php

namespace App\Services\Grading;

use App\Enums\Achievement;
use App\Models\Student;

class StudentGradeRow
{
    /**
     * @param  array<int, float|null>  $summativeScores  Skor sumatif per learning_scope_id
     */
    public function __construct(
        public readonly Student $student,
        public readonly array $summativeScores,
        public readonly ?string $summativeAverageRaw,
        public readonly ?int $summativeAverage,
        public readonly float|int|string|null $stsScore,
        public readonly ?string $finalScoreRaw,
        public readonly ?int $finalScore,
        public readonly ?Achievement $achievement,
        public readonly ?string $description,
        public readonly bool $isDescriptionOverridden,
        public readonly string $statusLabel,
        public readonly bool $isComplete,
    ) {}

    public function getFormattedFinalScore(string $placeholder = '–'): string
    {
        return $this->finalScore !== null ? (string) $this->finalScore : $placeholder;
    }

    public function getFormattedSummativeAverage(string $placeholder = '–'): string
    {
        return $this->summativeAverage !== null ? (string) $this->summativeAverage : $placeholder;
    }

    public function getFormattedStsScore(string $placeholder = '–'): string
    {
        if ($this->stsScore === null || $this->stsScore === '') {
            return $placeholder;
        }

        // Tampilkan bulat atau sesuai kebutuhan integer
        return (string) (int) round((float) $this->stsScore, 0, PHP_ROUND_HALF_UP);
    }
}
