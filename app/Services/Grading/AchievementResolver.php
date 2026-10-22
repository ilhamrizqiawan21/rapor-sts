<?php

namespace App\Services\Grading;

use App\Enums\Achievement;

class AchievementResolver
{
    /**
     * Tentukan predikat ketercapaian dari nilai n (integer hasil pembulatan) dan KKTP k (ADR-014).
     */
    public function resolve(?int $roundedScore, float|int $kktp): ?Achievement
    {
        if ($roundedScore === null) {
            return null;
        }

        $k = (float) $kktp;
        $pita = (100.0 - $k) / 3.0;

        if ($roundedScore < $k) {
            return Achievement::BelumTercapai;
        }

        if ($roundedScore < ($k + $pita)) {
            return Achievement::Cukup;
        }

        if ($roundedScore < ($k + (2.0 * $pita))) {
            return Achievement::Baik;
        }

        return Achievement::SangatBaik;
    }

    /**
     * Hitung rentang batas integer untuk tiap predikat berdasarkan nilai KKTP.
     * Dipakai pada modal input nilai, legenda, dan pratinjau interval rapor.
     *
     * @return array<string, array{achievement: Achievement, label: string, min: int, max: int, text: string}>
     */
    public function getIntervals(float|int $kktp): array
    {
        $k = (float) $kktp;
        $pita = (100.0 - $k) / 3.0;

        $kInt = (int) ceil($k);
        $b1 = (int) ceil($k + $pita);
        $b2 = (int) ceil($k + (2.0 * $pita));

        return [
            Achievement::BelumTercapai->value => [
                'achievement' => Achievement::BelumTercapai,
                'label' => Achievement::BelumTercapai->label(),
                'min' => 0,
                'max' => max(0, $kInt - 1),
                'text' => "< {$kInt}",
            ],
            Achievement::Cukup->value => [
                'achievement' => Achievement::Cukup,
                'label' => Achievement::Cukup->label(),
                'min' => $kInt,
                'max' => $b1 - 1,
                'text' => "{$kInt}–".($b1 - 1),
            ],
            Achievement::Baik->value => [
                'achievement' => Achievement::Baik,
                'label' => Achievement::Baik->label(),
                'min' => $b1,
                'max' => $b2 - 1,
                'text' => "{$b1}–".($b2 - 1),
            ],
            Achievement::SangatBaik->value => [
                'achievement' => Achievement::SangatBaik,
                'label' => Achievement::SangatBaik->label(),
                'min' => $b2,
                'max' => 100,
                'text' => "{$b2}–100",
            ],
        ];
    }
}
