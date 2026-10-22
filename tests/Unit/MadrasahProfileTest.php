<?php

namespace Tests\Unit;

use App\Models\MadrasahProfile;
use PHPUnit\Framework\TestCase;

class MadrasahProfileTest extends TestCase
{
    public function test_format_region_skips_empty_and_dash_parts(): void
    {
        $this->assertSame('Kecamatan A, Kabupaten B - Provinsi C', MadrasahProfile::formatRegion('A', 'B', 'Provinsi C'));
        $this->assertSame('Kabupaten B', MadrasahProfile::formatRegion('-', 'B', null));
        $this->assertSame('Kecamatan A, Kabupaten B', MadrasahProfile::formatRegion('A', 'B', '-'));
        $this->assertSame('', MadrasahProfile::formatRegion('-', '', '–'));
    }
}
