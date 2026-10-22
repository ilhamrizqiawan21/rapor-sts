<?php

namespace App\Services\Grading;

use InvalidArgumentException;

class GradeCalculator
{
    public const DEFAULT_SCALE = 4;

    /**
     * Hitung rerata nilai sumatif (LM) siswa.
     * Mengabaikan nilai null. Mengembalikan null jika tidak ada satu pun nilai valid.
     *
     * @param  array<int, float|int|string|null>  $scores
     */
    public function calculateSummativeAverage(array $scores, int $scale = self::DEFAULT_SCALE): ?string
    {
        $validScores = array_values(array_filter($scores, static fn ($v) => $v !== null && $v !== ''));

        $count = count($validScores);
        if ($count === 0) {
            return null;
        }

        $sum = '0';
        foreach ($validScores as $score) {
            $sum = bcadd($sum, (string) $score, $scale);
        }

        return bcdiv($sum, (string) $count, $scale);
    }

    /**
     * Hitung nilai akhir rapor STS dari rerata sumatif, nilai STS, dan bobot (ADR-015).
     *
     * Aturan:
     * - rerata = NULL                      → nilai_rapor = NULL
     * - wT = 0                             → nilai_rapor = rerata
     * - wS = 0                             → nilai_rapor = sts (NULL bila sts NULL)
     * - sts = NULL                         → nilai_rapor = NULL
     * - selain itu                         → nilai_rapor = (rerata × wS + sts × wT) / (wS + wT)
     * - jika wS = 0 dan wT = 0             → throw InvalidArgumentException
     */
    public function calculateFinalScore(
        ?string $summativeAvg,
        float|int|string|null $stsScore,
        float|int|string $weightSummative,
        float|int|string $weightSts,
        int $scale = self::DEFAULT_SCALE
    ): ?string {
        $wS = (string) $weightSummative;
        $wT = (string) $weightSts;

        // Cek jika kedua bobot nol
        if (bccomp($wS, '0', $scale) === 0 && bccomp($wT, '0', $scale) === 0) {
            throw new InvalidArgumentException('Bobot sumatif dan STS tidak boleh keduanya nol.');
        }

        // Jika bobot STS nol, nilai rapor murni dari sumatif
        if (bccomp($wT, '0', $scale) === 0) {
            return $summativeAvg;
        }

        // Jika bobot sumatif nol, nilai rapor murni dari STS
        if (bccomp($wS, '0', $scale) === 0) {
            return $stsScore !== null && $stsScore !== '' ? (string) $stsScore : null;
        }

        // Jika rerata sumatif belum ada
        if ($summativeAvg === null || $summativeAvg === '') {
            return null;
        }

        // Jika nilai STS belum ada
        if ($stsScore === null || $stsScore === '') {
            return null;
        }

        $stsStr = (string) $stsScore;

        $termSummative = bcmul($summativeAvg, $wS, $scale);
        $termSts = bcmul($stsStr, $wT, $scale);
        $numerator = bcadd($termSummative, $termSts, $scale);
        $denominator = bcadd($wS, $wT, $scale);

        return bcdiv($numerator, $denominator, $scale);
    }

    /**
     * Pembulatan nilai ke integer half-up (79.5 -> 80, 79.49 -> 79).
     */
    public function roundHalfUp(float|int|string|null $score): ?int
    {
        if ($score === null || $score === '') {
            return null;
        }

        return (int) round((float) $score, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Format skor ke tampilan integer (atau placeholder jika null).
     */
    public function formatScore(float|int|string|null $score, string $placeholder = '–'): string
    {
        $rounded = $this->roundHalfUp($score);

        return $rounded !== null ? (string) $rounded : $placeholder;
    }

    /**
     * Format rata-rata kelas/madrasah: 1 desimal dengan koma (81,4).
     */
    public function formatAverage(?float $avg, string $placeholder = '–'): string
    {
        if ($avg === null) {
            return $placeholder;
        }

        return number_format($avg, 1, ',', '');
    }

    /**
     * Evaluasi status kelengkapan nilai siswa untuk UI & validasi.
     */
    public function determineStatusLabel(?string $summativeAvg, float|int|string|null $stsScore, float|int|string $weightSts): string
    {
        if ($summativeAvg === null || $summativeAvg === '') {
            return 'Belum ada nilai sumatif';
        }

        $wT = (string) $weightSts;
        if (bccomp($wT, '0', self::DEFAULT_SCALE) > 0 && ($stsScore === null || $stsScore === '')) {
            return 'Nilai STS belum diisi';
        }

        return 'Lengkap';
    }
}
