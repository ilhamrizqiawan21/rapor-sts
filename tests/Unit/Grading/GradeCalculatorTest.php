<?php

namespace Tests\Unit\Grading;

use App\Services\Grading\GradeCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GradeCalculatorTest extends TestCase
{
    private GradeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new GradeCalculator;
    }

    /**
     * E1: Nilai LM: 85, 82, 85 | STS: 80 | Bobot: 50:50
     * Rerata mentah: 84 | Nilai rapor: 82 -> 82
     */
    public function test_example_e1(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([85, 82, 85]);
        $this->assertEquals('84.0000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, 80, 50, 50);
        $this->assertEquals('82.0000', $final);
        $this->assertSame(82, $this->calculator->roundHalfUp($final));
        $this->assertSame('82', $this->calculator->formatScore($final));
    }

    /**
     * E2: Nilai LM: 92, 90, 94 | STS: 90 | Bobot: 50:50
     * Rerata mentah: 92 | Nilai rapor: 91 -> 91
     */
    public function test_example_e2(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([92, 90, 94]);
        $this->assertEquals('92.0000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, 90, 50, 50);
        $this->assertEquals('91.0000', $final);
        $this->assertSame(91, $this->calculator->roundHalfUp($final));
        $this->assertSame('91', $this->calculator->formatScore($final));
    }

    /**
     * E3: Nilai LM: 78, 70, 74 | STS: 70 | Bobot: 50:50
     * Rerata mentah: 74 | Nilai rapor: 72 -> 72
     */
    public function test_example_e3(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([78, 70, 74]);
        $this->assertEquals('74.0000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, 70, 50, 50);
        $this->assertEquals('72.0000', $final);
        $this->assertSame(72, $this->calculator->roundHalfUp($final));
        $this->assertSame('72', $this->calculator->formatScore($final));
    }

    /**
     * E4: Nilai LM: 80, 85 | STS: 77 | Bobot: 60:40
     * Rerata mentah: 82.5 | Nilai rapor: 80.3 -> 80
     */
    public function test_example_e4(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([80, 85]);
        $this->assertEquals('82.5000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, 77, 60, 40);
        $this->assertEquals('80.3000', $final);
        $this->assertSame(80, $this->calculator->roundHalfUp($final));
        $this->assertSame('80', $this->calculator->formatScore($final));
    }

    /**
     * E5: Nilai LM: 80, 85 | STS: NULL | Bobot: 50:50
     * Rerata mentah: 82.5 | Nilai rapor: NULL -> –
     */
    public function test_example_e5(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([80, 85]);
        $this->assertEquals('82.5000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, null, 50, 50);
        $this->assertNull($final);
        $this->assertNull($this->calculator->roundHalfUp($final));
        $this->assertSame('–', $this->calculator->formatScore($final));
    }

    /**
     * E6: Nilai LM: 83, 86 | STS: NULL | Bobot: 50:0
     * Rerata mentah: 84.5 | Nilai rapor: 84.5 -> 85
     */
    public function test_example_e6(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([83, 86]);
        $this->assertEquals('84.5000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, null, 50, 0);
        $this->assertEquals('84.5000', $final);
        $this->assertSame(85, $this->calculator->roundHalfUp($final));
        $this->assertSame('85', $this->calculator->formatScore($final));
    }

    /**
     * E7: Nilai LM: 80, NULL, 90 | STS: 85 | Bobot: 50:50
     * Rerata mentah: 85 | Nilai rapor: 85 -> 85
     */
    public function test_example_e7(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([80, null, 90]);
        $this->assertEquals('85.0000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, 85, 50, 50);
        $this->assertEquals('85.0000', $final);
        $this->assertSame(85, $this->calculator->roundHalfUp($final));
        $this->assertSame('85', $this->calculator->formatScore($final));
    }

    /**
     * E8: Nilai LM: 79, 80 | STS: – | Bobot: 50:0
     * Rerata mentah: 79.5 | Nilai rapor: 79.5 -> 80 (half-up)
     */
    public function test_example_e8(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([79, 80]);
        $this->assertEquals('79.5000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, null, 50, 0);
        $this->assertEquals('79.5000', $final);
        $this->assertSame(80, $this->calculator->roundHalfUp($final));
        $this->assertSame('80', $this->calculator->formatScore($final));
    }

    /**
     * E9: Nilai LM: 79, 79.98 | STS: – | Bobot: 50:0
     * Rerata mentah: 79.49 | Nilai rapor: 79.49 -> 79
     */
    public function test_example_e9(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([79, 79.98]);
        $this->assertEquals('79.4900', $avg);

        $final = $this->calculator->calculateFinalScore($avg, null, 50, 0);
        $this->assertEquals('79.4900', $final);
        $this->assertSame(79, $this->calculator->roundHalfUp($final));
        $this->assertSame('79', $this->calculator->formatScore($final));
    }

    /**
     * E10: Nilai LM: (tidak ada) | STS: 90 | Bobot: 50:50
     * Rerata mentah: NULL | Nilai rapor: NULL -> –
     */
    public function test_example_e10(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([]);
        $this->assertNull($avg);

        $final = $this->calculator->calculateFinalScore($avg, 90, 50, 50);
        $this->assertNull($final);
        $this->assertNull($this->calculator->roundHalfUp($final));
        $this->assertSame('–', $this->calculator->formatScore($final));
    }

    /**
     * E11: Nilai LM: 70, 80 | STS: 60 | Bobot: 0:100
     * Rerata mentah: 75 | Nilai rapor: 60 -> 60
     */
    public function test_example_e11(): void
    {
        $avg = $this->calculator->calculateSummativeAverage([70, 80]);
        $this->assertEquals('75.0000', $avg);

        $final = $this->calculator->calculateFinalScore($avg, 60, 0, 100);
        $this->assertEquals('60', $final);
        $this->assertSame(60, $this->calculator->roundHalfUp($final));
        $this->assertSame('60', $this->calculator->formatScore($final));
    }

    /**
     * Uji presisi BCMath: penjumlahan desimal terhindar dari floating point precision error.
     */
    public function test_bcmath_precision_avoids_floating_point_glitches(): void
    {
        // Dalam float murni: 79 + 79.98 = 158.98, tapi 158.98 / 2 kadang menghasilkan 79.49000000000001
        $avg = $this->calculator->calculateSummativeAverage(['79.00', '79.98']);
        $this->assertSame('79.4900', $avg);
    }

    /**
     * Uji jika bobot sumatif dan STS keduanya 0 (harus ditolak).
     */
    public function test_zero_weights_throws_invalid_argument_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculateFinalScore('85.0000', 80, 0, 0);
    }

    /**
     * Uji format rata-rata kelas/madrasah (1 desimal koma).
     */
    public function test_format_average_displays_one_decimal_with_comma(): void
    {
        $this->assertSame('81,4', $this->calculator->formatAverage(81.4285));
        $this->assertSame('80,0', $this->calculator->formatAverage(80.0));
        $this->assertSame('–', $this->calculator->formatAverage(null));
    }

    /**
     * Uji label status kelengkapan nilai siswa.
     */
    public function test_determine_status_label(): void
    {
        // Belum ada sumatif
        $this->assertSame('Belum ada nilai sumatif', $this->calculator->determineStatusLabel(null, 80, 50));

        // Ada sumatif, tapi STS kosong dan bobot STS > 0
        $this->assertSame('Nilai STS belum diisi', $this->calculator->determineStatusLabel('85.0000', null, 50));

        // Ada sumatif, STS kosong tapi bobot STS 0 -> Lengkap
        $this->assertSame('Lengkap', $this->calculator->determineStatusLabel('85.0000', null, 0));

        // Lengkap
        $this->assertSame('Lengkap', $this->calculator->determineStatusLabel('85.0000', 80, 50));
    }
}
