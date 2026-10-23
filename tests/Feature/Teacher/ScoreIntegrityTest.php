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

class ScoreIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private TeachingAssignment $assignment;

    private Student $inClass;

    private Student $outside;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $this->assignment = TeachingAssignment::create([
            'period_id' => $period->id, 'classroom_id' => $classroom->id,
            'subject_id' => Subject::first()->id, 'teacher_id' => $this->teacher->id,
        ]);
        $this->inClass = Student::factory()->create();
        $this->outside = Student::factory()->create();
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $this->inClass->id]);
    }

    public function test_sts_rejects_student_outside_class(): void
    {
        $this->actingAs($this->teacher)->put(route('guru.sts.update', $this->assignment), [
            'scores' => [$this->outside->id => 80],
        ])->assertSessionHasErrors('scores');

        $this->actingAs($this->teacher)->put(route('guru.sts.update', $this->assignment), [
            'scores' => [999999 => 80],
        ])->assertSessionHasErrors('scores');

        $this->actingAs($this->teacher)->put(route('guru.sts.update', $this->assignment), [
            'scores' => [$this->inClass->id => 80],
        ])->assertSessionHasNoErrors();
    }

    public function test_learning_scope_rejects_duplicate_sequence_and_foreign_students(): void
    {
        $url = route('guru.sumatif.store', $this->assignment);
        $payload = ['sequence' => 1, 'summary' => 'Bab 1', 'kktp' => 70, 'scores' => [$this->inClass->id => 90]];

        $this->actingAs($this->teacher)->post($url, $payload)->assertSessionHasNoErrors();
        $this->actingAs($this->teacher)->post($url, $payload)->assertSessionHasErrors('sequence');

        $this->actingAs($this->teacher)->post($url, ['sequence' => 2, 'summary' => 'Bab 2', 'kktp' => 70, 'scores' => [$this->outside->id => 90]])
            ->assertSessionHasErrors('scores');
    }
}
