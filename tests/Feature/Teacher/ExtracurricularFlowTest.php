<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivityDefinition;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtracurricularFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_creates_activity_and_assigned_teacher_sees_the_menu_without_participants(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $supervisor = User::factory()->guru()->create(['name' => 'Pembina Pramuka']);
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        Classroom::factory()->create(['academic_year_id' => $year->id]);

        $response = $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->post(route('operator.extracurricular.activities.update'), [
                'activities' => [
                    'pramuka' => [
                        'name' => 'Pramuka',
                        'supervisor_id' => $supervisor->id,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $activity = ExtracurricularActivityDefinition::firstOrFail();
        $this->assertSame('Pramuka', $activity->name);
        $this->assertSame($supervisor->id, $activity->supervisor_id);

        $this->withSession(['period_id' => $period->id])
            ->actingAs($operator)
            ->get(route('operator.extracurricular.activities.index'))
            ->assertOk()
            ->assertSee('Kegiatan Ekstrakurikuler')
            ->assertSee('Pembina Pramuka');

        $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->get(route('guru.ekstrakurikuler.index'))
            ->assertOk()
            ->assertSee('Pramuka')
            ->assertSee('Belum ada peserta');
    }

    public function test_supervisor_selects_participants_per_class_and_saves_a_to_d_grade(): void
    {
        $supervisor = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $studentOne = Student::factory()->create(['name' => 'Peserta Satu']);
        $studentTwo = Student::factory()->create(['name' => 'Bukan Peserta']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentOne->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentTwo->id]);
        $activity = ExtracurricularActivityDefinition::create([
            'period_id' => $period->id,
            'name' => 'Paskibra',
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->post(route('guru.ekstrakurikuler.update'), [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'participant_ids' => [$studentOne->id],
                'records' => [
                    $studentOne->id => [
                        'grade' => 'A',
                        'description' => 'Sangat aktif.',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $studentOne->id,
            'activity_definition_id' => $activity->id,
            'predicate' => 'A',
            'description' => 'Sangat aktif.',
        ]);
        $this->assertDatabaseMissing('extracurricular_records', [
            'classroom_id' => $classroom->id,
            'student_id' => $studentTwo->id,
            'activity_definition_id' => $activity->id,
        ]);

        $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->get(route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
            ]))
            ->assertOk()
            ->assertSee('Peserta Satu')
            ->assertSee('A — Sangat Baik');
    }

    public function test_teacher_without_activity_assignment_cannot_open_extracurricular_menu(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        Classroom::factory()->create(['academic_year_id' => $year->id]);

        $this->withSession(['period_id' => $period->id])
            ->actingAs($teacher)
            ->get(route('guru.ekstrakurikuler.index'))
            ->assertForbidden();
    }
}
