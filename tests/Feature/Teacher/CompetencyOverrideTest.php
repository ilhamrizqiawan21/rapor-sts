<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetencyOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_teacher_can_override_and_reset_competency_description(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create();

        $overrideText = 'Siswa menunjukkan kemampuan luar biasa dalam memahami seluruh materi.';

        $response = $this->actingAs($teacher)->put(route('guru.capaian.update', [$assignment, $student]), [
            'description' => $overrideText,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('competency_overrides', [
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'description' => $overrideText,
            'updated_by' => $teacher->id,
        ]);

        // Reset ke otomatis
        $resetResponse = $this->actingAs($teacher)->delete(route('guru.capaian.destroy', [$assignment, $student]));
        $resetResponse->assertRedirect();
        $resetResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('competency_overrides', [
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_override_description_longer_than_300_characters_is_rejected(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create();

        $longText = str_repeat('A', 301);

        $response = $this->actingAs($teacher)->put(route('guru.capaian.update', [$assignment, $student]), [
            'description' => $longText,
        ]);

        $response->assertSessionHasErrors('description');
    }
}
