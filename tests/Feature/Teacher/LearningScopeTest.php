<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\LearningScope;
use App\Models\Period;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SummativeScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_teacher_can_view_sumatif_page_and_create_learning_scope_with_scores(): void
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

        $student1 = Student::factory()->create(['name' => 'Ahmad']);
        $student2 = Student::factory()->create(['name' => 'Budi']);
        foreach ([$student1, $student2] as $s) {
            Enrollment::create([
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'student_id' => $s->id,
            ]);
        }

        $viewResponse = $this->actingAs($teacher)->get(route('guru.sumatif.index', $assignment));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Nilai Sumatif Lingkup Materi');

        // Tambah LM 1
        $storeResponse = $this->actingAs($teacher)->post(route('guru.sumatif.store', $assignment), [
            'sequence' => 1,
            'summary' => 'menganalisis tata cara bersuci',
            'kktp' => 70.00,
            'scores' => [
                $student1->id => 85.00,
                $student2->id => 80.00,
            ],
        ]);

        $storeResponse->assertRedirect(route('guru.sumatif.index', $assignment));

        $this->assertDatabaseHas('learning_scopes', [
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'menganalisis tata cara bersuci',
        ]);

        $scope = LearningScope::where('teaching_assignment_id', $assignment->id)->first();

        $this->assertDatabaseHas('summative_scores', [
            'learning_scope_id' => $scope->id,
            'student_id' => $student1->id,
            'score' => 85.00,
        ]);
        $this->assertDatabaseHas('summative_scores', [
            'learning_scope_id' => $scope->id,
            'student_id' => $student2->id,
            'score' => 80.00,
        ]);
    }

    public function test_teacher_can_update_learning_scope_and_scores(): void
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
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $scope = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'ringkasan lama',
            'kktp' => 70.00,
        ]);

        SummativeScore::create([
            'learning_scope_id' => $scope->id,
            'student_id' => $student->id,
            'score' => 75.00,
        ]);

        $response = $this->actingAs($teacher)->put(route('guru.sumatif.update', [$assignment, $scope]), [
            'sequence' => 1,
            'summary' => 'ringkasan baru diperbarui',
            'kktp' => 72.00,
            'scores' => [
                $student->id => 88.00,
            ],
        ]);

        $response->assertRedirect(route('guru.sumatif.index', $assignment));

        $this->assertDatabaseHas('learning_scopes', [
            'id' => $scope->id,
            'summary' => 'ringkasan baru diperbarui',
            'kktp' => 72.00,
        ]);

        $this->assertDatabaseHas('summative_scores', [
            'learning_scope_id' => $scope->id,
            'student_id' => $student->id,
            'score' => 88.00,
        ]);
    }

    public function test_teacher_can_delete_learning_scope_and_its_scores(): void
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
        $scope = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'materi yang akan dihapus',
            'kktp' => 70.00,
        ]);

        $score = SummativeScore::create([
            'learning_scope_id' => $scope->id,
            'student_id' => $student->id,
            'score' => 80.00,
        ]);

        $response = $this->actingAs($teacher)->delete(route('guru.sumatif.destroy', [$assignment, $scope]));
        $response->assertRedirect(route('guru.sumatif.index', $assignment));

        $this->assertDatabaseMissing('learning_scopes', ['id' => $scope->id]);
        $this->assertDatabaseMissing('summative_scores', ['id' => $score->id]);
    }
}
