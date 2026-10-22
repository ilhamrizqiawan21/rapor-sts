<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectAndClassroomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_create_subject(): void
    {
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->post(route('operator.subjects.store'), [
            'code' => 'KDG',
            'name' => 'Kaidah Dasar Guru',
            'curriculum' => 'merdeka',
            'group' => 'mulok',
            'sort_order' => 20,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['code' => 'KDG', 'name' => 'Kaidah Dasar Guru']);
    }

    public function test_duplicate_subject_code_in_same_curriculum_is_rejected(): void
    {
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->post(route('operator.subjects.store'), [
            'code' => 'MTK', // MTK sudah ada di Merdeka dari seeder
            'name' => 'Matematika Peminatan',
            'curriculum' => 'merdeka',
            'group' => 'umum',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_operator_can_create_batch_classrooms(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($operator)->post(route('operator.classrooms.batch'), [
            'academic_year_id' => $year->id,
            'level' => 7,
            'prefix' => 'VII.',
            'parallels' => 'A, B, C',
            'curriculum' => 'merdeka',
            'phase' => 'D',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', ['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $this->assertDatabaseHas('classrooms', ['academic_year_id' => $year->id, 'name' => 'VII.B']);
        $this->assertDatabaseHas('classrooms', ['academic_year_id' => $year->id, 'name' => 'VII.C']);
    }

    public function test_cannot_delete_classroom_with_enrolled_students(): void
    {
        $operator = User::where('username', 'admin')->first();
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.classrooms.destroy', $classroom));

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id]);
    }
}
