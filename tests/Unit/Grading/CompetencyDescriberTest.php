<?php

namespace Tests\Unit\Grading;

use App\Enums\Achievement;
use App\Services\Grading\CompetencyDescriber;
use PHPUnit\Framework\TestCase;

class CompetencyDescriberTest extends TestCase
{
    private CompetencyDescriber $describer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->describer = new CompetencyDescriber;
    }

    /**
     * D1: LM1 "menganalisis tata cara bersuci dari hadas dan najis" = 85,
     * LM2 "mempraktikkan wudu" = 82, nilai rapor 82 (Baik)
     * -> "Menunjukkan penguasaan yang baik dalam menganalisis tata cara bersuci dari hadas dan najis."
     */
    public function test_example_d1(): void
    {
        $scopes = [
            [
                'id' => 1,
                'sequence' => 1,
                'summary' => 'menganalisis tata cara bersuci dari hadas dan najis',
                'kktp' => 70,
                'score' => 85,
            ],
            [
                'id' => 2,
                'sequence' => 2,
                'summary' => 'mempraktikkan wudu',
                'kktp' => 70,
                'score' => 82,
            ],
        ];

        $desc = $this->describer->describe(Achievement::Baik, $scopes);

        $this->assertSame(
            'Menunjukkan penguasaan yang baik dalam menganalisis tata cara bersuci dari hadas dan najis.',
            $desc
        );
    }

    /**
     * D2: LM1 "mengidentifikasi macam-macam najis" = 78,
     * LM2 "menyucikan najis" = 66 (KKTP 70), nilai rapor 72 (Cukup)
     * -> "Menunjukkan penguasaan yang cukup baik dalam mengidentifikasi macam-macam najis. Perlu bimbingan dalam menyucikan najis."
     */
    public function test_example_d2(): void
    {
        $scopes = [
            [
                'id' => 1,
                'sequence' => 1,
                'summary' => 'mengidentifikasi macam-macam najis',
                'kktp' => 70,
                'score' => 78,
            ],
            [
                'id' => 2,
                'sequence' => 2,
                'summary' => 'menyucikan najis',
                'kktp' => 70,
                'score' => 66,
            ],
        ];

        $desc = $this->describer->describe(Achievement::Cukup, $scopes);

        $this->assertSame(
            'Menunjukkan penguasaan yang cukup baik dalam mengidentifikasi macam-macam najis. Perlu bimbingan dalam menyucikan najis.',
            $desc
        );
    }

    /**
     * Uji teks override: jika ada override dari guru, gunakan teks override.
     */
    public function test_override_text_takes_precedence(): void
    {
        $scopes = [
            [
                'id' => 1,
                'sequence' => 1,
                'summary' => 'menganalisis tata cara bersuci dari hadas dan najis',
                'kktp' => 70,
                'score' => 85,
            ],
        ];

        $override = 'Siswa sangat aktif dan menguasai seluruh materi bersuci dengan sempurna.';
        $desc = $this->describer->describe(Achievement::Baik, $scopes, $override);

        $this->assertSame($override, $desc);
    }

    /**
     * Uji pemecah seri (tie-break): jika skor tertinggi atau terendah sama, pilih sequence terkecil.
     */
    public function test_tie_breaking_picks_lowest_sequence(): void
    {
        $scopes = [
            [
                'id' => 2,
                'sequence' => 2,
                'summary' => 'materi kedua',
                'kktp' => 70,
                'score' => 90,
            ],
            [
                'id' => 1,
                'sequence' => 1,
                'summary' => 'materi pertama',
                'kktp' => 70,
                'score' => 90,
            ],
            [
                'id' => 3,
                'sequence' => 3,
                'summary' => 'materi remedial A',
                'kktp' => 70,
                'score' => 60,
            ],
            [
                'id' => 4,
                'sequence' => 4,
                'summary' => 'materi remedial B',
                'kktp' => 70,
                'score' => 60,
            ],
        ];

        $desc = $this->describer->describe(Achievement::SangatBaik, $scopes);

        // Harus memilih materi pertama (sequence 1) dan materi remedial A (sequence 3)
        $this->assertSame(
            'Menunjukkan penguasaan yang sangat baik dalam materi pertama. Perlu bimbingan dalam materi remedial A.',
            $desc
        );
    }

    /**
     * Uji ringkasan dengan spasi berlebih dan titik di akhir.
     */
    public function test_cleans_trailing_period_and_whitespace(): void
    {
        $scopes = [
            [
                'id' => 1,
                'sequence' => 1,
                'summary' => '  memahami konsep shalat fardhu.  ',
                'kktp' => 70,
                'score' => 85,
            ],
        ];

        $desc = $this->describer->describe(Achievement::Baik, $scopes);

        $this->assertSame(
            'Menunjukkan penguasaan yang baik dalam memahami konsep shalat fardhu.',
            $desc
        );
    }

    /**
     * Uji jika achievement null atau tidak ada nilai valid.
     */
    public function test_returns_null_when_achievement_or_scores_are_null(): void
    {
        $this->assertNull($this->describer->describe(null, []));

        $scopesWithNull = [
            [
                'id' => 1,
                'sequence' => 1,
                'summary' => 'materi A',
                'kktp' => 70,
                'score' => null,
            ],
        ];

        $this->assertNull($this->describer->describe(Achievement::Baik, $scopesWithNull));
    }
}
