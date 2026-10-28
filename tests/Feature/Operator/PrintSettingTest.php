<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Period;
use App\Models\PrintSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_non_operator_cannot_access_print_settings(): void
    {
        $teacher = User::factory()->guru()->create();

        $response = $this->actingAs($teacher)->get(route('operator.print-settings.edit'));
        $response->assertStatus(403);
    }

    public function test_operator_can_view_print_settings(): void
    {
        $operator = User::factory()->operator()->create();
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $response = $this->actingAs($operator)->get(route('operator.print-settings.edit'));
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Cetak Rapor STS');
        $response->assertSee('Capaian Kompetensi');
        $response->assertSee('value="Nilai Akhir"', false);
        $response->assertDontSee('Watermark');
    }

    public function test_operator_can_update_print_settings(): void
    {
        $operator = User::factory()->operator()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $response = $this->actingAs($operator)->post(route('operator.print-settings.update'), [
            'report_title' => 'RAPOR TENGAH SEMESTER',
            'print_place' => 'Cimahi',
            'print_date' => '2026-11-01',
            'score_label' => 'Nilai STS',
            'paper_size' => 'F4',
            'sections' => [
                'show_competency' => '1',
                'show_attendance' => '1',
                'show_note' => '1',
                'show_total_score' => '1',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('print_settings', [
            'period_id' => $period->id,
            'report_title' => 'RAPOR TENGAH SEMESTER',
            'print_place' => 'Cimahi',
            'paper_size' => 'F4',
        ]);

        $setting = PrintSetting::where('period_id', $period->id)->first();
        $this->assertTrue($setting->isSectionVisible('show_total_score'));
        $this->assertFalse($setting->isSectionVisible('show_qr'));
    }
}
