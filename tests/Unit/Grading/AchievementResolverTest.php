<?php

namespace Tests\Unit\Grading;

use App\Enums\Achievement;
use App\Services\Grading\AchievementResolver;
use PHPUnit\Framework\TestCase;

class AchievementResolverTest extends TestCase
{
    private AchievementResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AchievementResolver;
    }

    public function test_kktp_70_intervals(): void
    {
        $intervals = $this->resolver->getIntervals(70);

        $this->assertSame('< 70', $intervals['belum_tercapai']['text']);
        $this->assertSame(0, $intervals['belum_tercapai']['min']);
        $this->assertSame(69, $intervals['belum_tercapai']['max']);

        $this->assertSame('70–79', $intervals['cukup']['text']);
        $this->assertSame(70, $intervals['cukup']['min']);
        $this->assertSame(79, $intervals['cukup']['max']);

        $this->assertSame('80–89', $intervals['baik']['text']);
        $this->assertSame(80, $intervals['baik']['min']);
        $this->assertSame(89, $intervals['baik']['max']);

        $this->assertSame('90–100', $intervals['sangat_baik']['text']);
        $this->assertSame(90, $intervals['sangat_baik']['min']);
        $this->assertSame(100, $intervals['sangat_baik']['max']);
    }

    public function test_kktp_70_resolutions(): void
    {
        $this->assertSame(Achievement::BelumTercapai, $this->resolver->resolve(69, 70));
        $this->assertSame(Achievement::Cukup, $this->resolver->resolve(70, 70));
        $this->assertSame(Achievement::Cukup, $this->resolver->resolve(79, 70));
        $this->assertSame(Achievement::Baik, $this->resolver->resolve(80, 70));
        $this->assertSame(Achievement::Baik, $this->resolver->resolve(89, 70));
        $this->assertSame(Achievement::SangatBaik, $this->resolver->resolve(90, 70));
        $this->assertSame(Achievement::SangatBaik, $this->resolver->resolve(100, 70));
    }

    public function test_kktp_75_intervals(): void
    {
        $intervals = $this->resolver->getIntervals(75);

        $this->assertSame('< 75', $intervals['belum_tercapai']['text']);
        $this->assertSame(0, $intervals['belum_tercapai']['min']);
        $this->assertSame(74, $intervals['belum_tercapai']['max']);

        $this->assertSame('75–83', $intervals['cukup']['text']);
        $this->assertSame(75, $intervals['cukup']['min']);
        $this->assertSame(83, $intervals['cukup']['max']);

        $this->assertSame('84–91', $intervals['baik']['text']);
        $this->assertSame(84, $intervals['baik']['min']);
        $this->assertSame(91, $intervals['baik']['max']);

        $this->assertSame('92–100', $intervals['sangat_baik']['text']);
        $this->assertSame(92, $intervals['sangat_baik']['min']);
        $this->assertSame(100, $intervals['sangat_baik']['max']);
    }

    /**
     * Sesuai 07 §6: "Predikat dengan KKTP 75: 74 → Belum, 75 → Cukup, 83 → Cukup, 84 → Baik, 91 → Baik, 92 → Sangat Baik."
     */
    public function test_kktp_75_resolutions_from_specification(): void
    {
        $this->assertSame(Achievement::BelumTercapai, $this->resolver->resolve(74, 75));
        $this->assertSame(Achievement::Cukup, $this->resolver->resolve(75, 75));
        $this->assertSame(Achievement::Cukup, $this->resolver->resolve(83, 75));
        $this->assertSame(Achievement::Baik, $this->resolver->resolve(84, 75));
        $this->assertSame(Achievement::Baik, $this->resolver->resolve(91, 75));
        $this->assertSame(Achievement::SangatBaik, $this->resolver->resolve(92, 75));
    }

    public function test_null_score_returns_null_achievement(): void
    {
        $this->assertNull($this->resolver->resolve(null, 70));
    }
}
