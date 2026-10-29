<?php

namespace Tests\Feature\Homeroom;

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

    public function test_homeroom_teacher_can_view_and_save_student_achievements(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);
        $student = Student::factory()->create(['name' => 'Siswa Wali Kelas']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($teacher)
            ->withSession(['period_id' => $period->id])
            ->get(route('wali.prestasi.index'))
            ->assertOk()
            ->assertSee('Prestasi Siswa')
            ->assertSee('Siswa Wali Kelas');

        $this->actingAs($teacher)
            ->withSession(['period_id' => $period->id])
            ->post(route('wali.prestasi.update'), [
                'classroom_id' => $classroom->id,
                'achievements' => [
                    $student->id => [[
                        'name' => 'Olimpiade Matematika',
                        'level' => 'Kabupaten/Kota',
                        'rank' => 'Juara 1',
                    ]],
                ],
            ])
            ->assertRedirect(route('wali.prestasi.index'));

        $this->assertDatabaseHas('achievement_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'name' => 'Olimpiade Matematika',
            'rank' => 'Juara 1',
        ]);
    }

    public function test_non_homeroom_teacher_cannot_access_student_achievements(): void
    {
        $teacher = User::factory()->guru()->create();

        $this->actingAs($teacher)
            ->get(route('wali.prestasi.index'))
            ->assertForbidden();
    }

    public function test_homeroom_teacher_cannot_save_achievements_for_another_class(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);
        $otherClassroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII.B',
        ]);
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $otherClassroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($teacher)
            ->withSession(['period_id' => $period->id])
            ->post(route('wali.prestasi.update'), [
                'classroom_id' => $otherClassroom->id,
                'achievements' => [$student->id => [['name' => 'Prestasi Ilegal', 'level' => 'Sekolah']]],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('achievement_records', ['name' => 'Prestasi Ilegal']);
    }

    public function test_homeroom_teacher_cannot_update_achievements_after_class_is_locked(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'locked_by' => $teacher->id,
            'locked_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->withSession(['period_id' => $period->id])
            ->post(route('wali.prestasi.update'), [
                'classroom_id' => $classroom->id,
                'achievements' => [$student->id => [['name' => 'Prestasi Setelah Kunci', 'level' => 'Sekolah']]],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('achievement_records', ['name' => 'Prestasi Setelah Kunci']);
    }
}
