<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicYearAndPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_create_academic_year_and_auto_generates_periods(): void
    {
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->post(route('operator.lembaga.tahun.store'), [
            'name' => '2027/2028',
        ]);

        $response->assertRedirect(route('operator.lembaga.index'));

        $this->assertDatabaseHas('academic_years', ['name' => '2027/2028']);

        $year = AcademicYear::where('name', '2027/2028')->first();
        $this->assertCount(2, $year->periods);
        $this->assertDatabaseHas('periods', ['academic_year_id' => $year->id, 'semester' => 1]);
        $this->assertDatabaseHas('periods', ['academic_year_id' => $year->id, 'semester' => 2]);
    }

    public function test_operator_can_activate_period(): void
    {
        $operator = User::where('username', 'admin')->first();

        $year = AcademicYear::factory()->create(['name' => '2026/2027']);
        $p1 = Period::create(['academic_year_id' => $year->id, 'semester' => 1, 'is_active' => true]);
        $p2 = Period::create(['academic_year_id' => $year->id, 'semester' => 2, 'is_active' => false]);

        $response = $this->actingAs($operator)->post(route('operator.lembaga.periode.activate', $p2));

        $response->assertRedirect();

        $this->assertFalse($p1->fresh()->is_active);
        $this->assertTrue($p2->fresh()->is_active);
        $this->assertSame($p2->id, session('period_id'));
    }

    public function test_cannot_delete_academic_year_with_classrooms(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create(['name' => '2025/2026']);
        Classroom::factory()->create(['academic_year_id' => $year->id]);

        $response = $this->actingAs($operator)->delete(route('operator.lembaga.tahun.destroy', $year));

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    public function test_academic_year_name_must_use_consecutive_yyyy_yyyy_format(): void
    {
        $operator = User::where('username', 'admin')->first();

        foreach (['2027', 'abc', '2027/2029', '2027/2027', '27/28'] as $invalid) {
            $this->actingAs($operator)
                ->post(route('operator.lembaga.tahun.store'), ['name' => $invalid])
                ->assertSessionHasErrors('name');

            $this->assertDatabaseMissing('academic_years', ['name' => $invalid]);
        }
    }
}
