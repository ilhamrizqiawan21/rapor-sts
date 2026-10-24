<?php

namespace Tests\Unit;

use App\Enums\Achievement;
use App\Enums\Curriculum;
use App\Enums\Role;
use App\Enums\Semester;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_role_enum_values_and_labels(): void
    {
        $this->assertSame('operator', Role::Operator->value);
        $this->assertSame('guru', Role::Guru->value);
        $this->assertSame('kepala', Role::Kepala->value);

        $this->assertSame('Admin', Role::Operator->label());
        $this->assertSame('Guru', Role::Guru->label());
        $this->assertSame('Kepala Madrasah', Role::Kepala->label());
    }

    public function test_semester_enum_values_and_labels(): void
    {
        $this->assertSame(1, Semester::Ganjil->value);
        $this->assertSame(2, Semester::Genap->value);

        $this->assertSame('Ganjil', Semester::Ganjil->label());
        $this->assertSame('Genap', Semester::Genap->label());

        $this->assertSame('Semester Ganjil', Semester::Ganjil->fullLabel());
        $this->assertSame('Semester Genap', Semester::Genap->fullLabel());
    }

    public function test_curriculum_enum_values_and_labels(): void
    {
        $this->assertSame('merdeka', Curriculum::Merdeka->value);
        $this->assertSame('k13', Curriculum::K13->value);

        $this->assertSame('Kurikulum Merdeka', Curriculum::Merdeka->label());
        $this->assertSame('Kurikulum 2013', Curriculum::K13->label());
    }

    public function test_achievement_enum_values_labels_and_descriptors(): void
    {
        $this->assertSame('belum_tercapai', Achievement::BelumTercapai->value);
        $this->assertSame('cukup', Achievement::Cukup->value);
        $this->assertSame('baik', Achievement::Baik->value);
        $this->assertSame('sangat_baik', Achievement::SangatBaik->value);

        $this->assertSame('Belum Tercapai', Achievement::BelumTercapai->label());
        $this->assertSame('Cukup', Achievement::Cukup->label());
        $this->assertSame('Baik', Achievement::Baik->label());
        $this->assertSame('Sangat Baik', Achievement::SangatBaik->label());

        $this->assertSame('kurang', Achievement::BelumTercapai->descriptorWord());
        $this->assertSame('cukup baik', Achievement::Cukup->descriptorWord());
        $this->assertSame('baik', Achievement::Baik->descriptorWord());
        $this->assertSame('sangat baik', Achievement::SangatBaik->descriptorWord());
    }
}
