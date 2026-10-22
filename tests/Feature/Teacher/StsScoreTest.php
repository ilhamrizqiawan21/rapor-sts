<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StsScoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_teacher_can_view_sts_page_and_save_scores(): void
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
            'kktp' => 70.00,
        ]);

        $student1 = Student::factory()->create(['name' => 'Fauzan']);
        $student2 = Student::factory()->create(['name' => 'Aisyah']);

        foreach ([$student1, $student2] as $s) {
            Enrollment::create([
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'student_id' => $s->id,
            ]);
        }

        $viewResponse = $this->actingAs($teacher)->get(route('guru.sts.index', $assignment));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Nilai Sumatif Tengah Semester (STS)');

        $saveResponse = $this->actingAs($teacher)->put(route('guru.sts.update', $assignment), [
            'scores' => [
                $student1->id => 80.00,
                $student2->id => 90.00,
            ],
        ]);

        $saveResponse->assertRedirect();
        $saveResponse->assertSessionHas('success');

        $this->assertDatabaseHas('sts_scores', [
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student1->id,
            'score' => 80.00,
        ]);
        $this->assertDatabaseHas('sts_scores', [
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student2->id,
            'score' => 90.00,
        ]);
    }

    public function test_invalid_sts_scores_are_rejected(): void
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

        $response = $this->actingAs($teacher)->put(route('guru.sts.update', $assignment), [
            'scores' => [
                $student->id => 120.00, // > 100 invalid
            ],
        ]);

        $response->assertSessionHasErrors('scores.'.$student->id);
    }
}
