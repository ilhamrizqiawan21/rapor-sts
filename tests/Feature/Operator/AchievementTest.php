<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomLock;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_view_and_save_student_achievements_for_active_classroom(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $year = AcademicYear::factory()->create(['name' => '2036/2037']);
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII.A',
        ]);
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->get(route('operator.achievements.index', ['classroom_id' => $classroom->id]))
            ->assertOk()
            ->assertSee('Prestasi Siswa');

        $response = $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->post(route('operator.achievements.update'), [
                'classroom_id' => $classroom->id,
                'achievements' => [
                    $student->id => [
                        [
                            'name' => 'Olimpiade Matematika',
                            'level' => 'Kabupaten/Kota',
                            'rank' => 'Juara 1',
                            'achieved_at' => '2036-09-12',
                            'organizer' => 'Dinas Pendidikan',
                            'description' => 'Bidang matematika.',
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('operator.achievements.index', ['classroom_id' => $classroom->id]));
        $this->assertDatabaseHas('achievement_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'name' => 'Olimpiade Matematika',
            'rank' => 'Juara 1',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'save_achievements']);
    }

    public function test_operator_cannot_save_achievement_for_student_outside_selected_classroom(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $year = AcademicYear::factory()->create(['name' => '2037/2038']);
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $otherClassroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.B']);
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $otherClassroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->from(route('operator.achievements.index', ['classroom_id' => $classroom->id]))
            ->post(route('operator.achievements.update'), [
                'classroom_id' => $classroom->id,
                'achievements' => [$student->id => [['name' => 'Prestasi Ilegal', 'level' => 'Sekolah']]],
            ])
            ->assertSessionHasErrors('achievements');

        $this->assertDatabaseMissing('achievement_records', ['name' => 'Prestasi Ilegal']);
    }

    public function test_non_operator_cannot_access_student_achievements(): void
    {
        $teacher = User::factory()->guru()->create();

        $this->actingAs($teacher)
            ->get(route('operator.achievements.index'))
            ->assertForbidden();
    }

    public function test_operator_cannot_update_achievements_after_class_is_locked(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $year = AcademicYear::factory()->create(['name' => '2038/2039']);
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'locked_by' => $operator->id,
            'locked_at' => now(),
        ]);

        $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->post(route('operator.achievements.update'), [
                'classroom_id' => $classroom->id,
                'achievements' => [$student->id => [['name' => 'Prestasi Setelah Kunci', 'level' => 'Sekolah']]],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('achievement_records', ['name' => 'Prestasi Setelah Kunci']);
    }
}
