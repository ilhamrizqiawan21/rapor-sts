<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorRaporPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_status_and_print_pages_render_with_homeroom_teachers_without_lazy_loading(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        foreach (['VII.A', 'VII.B'] as $name) {
            Classroom::factory()->create([
                'academic_year_id' => $year->id,
                'homeroom_teacher_id' => User::factory()->guru()->create()->id,
                'name' => $name,
            ]);
        }

        $this->actingAs($operator)->get(route('operator.status.index'))->assertOk()->assertSee('Wali:');
        $this->actingAs($operator)->get(route('operator.rapor.index'))->assertOk()->assertSee('Wali:');
    }
}
