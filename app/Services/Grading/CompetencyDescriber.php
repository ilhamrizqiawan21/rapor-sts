<?php

namespace App\Services\Grading;

use App\Enums\Achievement;

class CompetencyDescriber
{
    public const MAX_LENGTH = 300;

    /**
     * Hasilkan teks deskripsi capaian kompetensi siswa (otomatis atau override).
     *
     * @param  Achievement|null  $achievement  Predikat nilai rapor siswa
     * @param  array<int, array{summary: string, sequence: int, score: float|int|string|null, kktp: float|int|string, id?: int|string}>  $scopesWithScores
     * @param  string|null  $override  Teks override dari tabel competency_overrides bila ada
     */
    public function describe(?Achievement $achievement, array $scopesWithScores, ?string $override = null): ?string
    {
        // 1. Jika ada teks override, gunakan override apa adanya (dipangkas spasi)
        if ($override !== null && trim($override) !== '') {
            return trim($override);
        }

        // 2. Jika nilai rapor NULL atau predikat NULL, tidak ada deskripsi
        if ($achievement === null) {
            return null;
        }

        // 3. Ambil LM yang memiliki nilai valid (abaikan nilai null)
        $validScopes = array_values(array_filter($scopesWithScores, static function ($item) {
            return isset($item['score']) && $item['score'] !== null && $item['score'] !== '';
        }));

        if (empty($validScopes)) {
            return null;
        }

        // 4. Cari lm_terbaik: skor tertinggi (seri -> sequence terkecil)
        usort($validScopes, static function ($a, $b) {
            $cmp = ((float) $b['score']) <=> ((float) $a['score']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return ((int) $a['sequence']) <=> ((int) $b['sequence']);
        });

        $best = $validScopes[0];
        $bestSummary = rtrim(trim($best['summary']), '.');
        $word = $achievement->descriptorWord();

        $sentence = "Menunjukkan penguasaan yang {$word} dalam {$bestSummary}";

        // 5. Cari lm_lemah: skor terendah yang < KKTP LM-nya, dan != lm_terbaik (seri -> sequence terkecil)
        $bestKey = $best['id'] ?? $best['sequence'];
        $weakCandidates = array_values(array_filter($validScopes, static function ($item) use ($bestKey) {
            $key = $item['id'] ?? $item['sequence'];
            $isBelowKktp = ((float) $item['score']) < ((float) $item['kktp']);

            return $isBelowKktp && $key !== $bestKey;
        }));

        if (! empty($weakCandidates)) {
            usort($weakCandidates, static function ($a, $b) {
                $cmp = ((float) $a['score']) <=> ((float) $b['score']);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return ((int) $a['sequence']) <=> ((int) $b['sequence']);
            });

            $weak = $weakCandidates[0];
            $weakSummary = rtrim(trim($weak['summary']), '.');
            $sentence .= ". Perlu bimbingan dalam {$weakSummary}";
        }

        $sentence .= '.';

        return $sentence;
    }
}
