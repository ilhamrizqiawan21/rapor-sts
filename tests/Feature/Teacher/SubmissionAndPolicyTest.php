<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomLock;
use App\Models\Enrollment;
use App\Models\LearningScope;
use App\Models\Period;
use App\Models\StsScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SummativeScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_teacher_can_view_assessment_summary_sheet(): void
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

        $response = $this->actingAs($teacher)->get(route('guru.penilaian.index', $assignment));
        $response->assertStatus(200);
        $response->assertSee('Penilaian Sumatif Tengah Semester');
    }

    public function test_other_teacher_cannot_view_or_update_assessment(): void
    {
        $teacherOwner = User::factory()->guru()->create();
        $otherTeacher = User::factory()->guru()->create();

        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacherOwner->id,
        ]);

        // Guru lain tidak boleh melihat penilaian
        $viewResponse = $this->actingAs($otherTeacher)->get(route('guru.penilaian.index', $assignment));
        $viewResponse->assertStatus(403);

        // Guru lain tidak boleh submit
        $submitResponse = $this->actingAs($otherTeacher)->post(route('guru.penilaian.submit', $assignment));
        $submitResponse->assertStatus(403);
    }

    public function test_submission_is_rejected_when_scores_are_incomplete(): void
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
            'weight_summative' => 50,
            'weight_sts' => 50,
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        // Belum ada nilai apa pun
        $response = $this->actingAs($teacher)->post(route('guru.penilaian.submit', $assignment));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertNull($assignment->fresh()->submitted_at);
    }

    public function test_submission_succeeds_when_all_scores_complete_and_locks_assignment(): void
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
            'weight_summative' => 50,
            'weight_sts' => 50,
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $lm = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'materi lengkap',
            'kktp' => 70,
        ]);

        SummativeScore::create([
            'learning_scope_id' => $lm->id,
            'student_id' => $student->id,
            'score' => 85,
        ]);

        StsScore::create([
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'score' => 85,
        ]);

        $response = $this->actingAs($teacher)->post(route('guru.penilaian.submit', $assignment));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertNotNull($assignment->fresh()->submitted_at);
        $this->assertSame($teacher->id, $assignment->fresh()->submitted_by);

        // Setelah kirim, guru tidak bisa lagi mengirim ulang atau mengubah nilai
        $resubmit = $this->actingAs($teacher)->post(route('guru.penilaian.submit', $assignment));
        $resubmit->assertStatus(403);

        $addLm = $this->actingAs($teacher)->post(route('guru.sumatif.store', $assignment), [
            'sequence' => 2,
            'summary' => 'materi baru',
            'kktp' => 70,
        ]);
        $addLm->assertStatus(403);
    }

    public function test_updating_is_forbidden_when_classroom_is_locked_by_homeroom(): void
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

        // Kelas dikunci pada periode ini
        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'locked_at' => now(),
        ]);

        // Kirim nilai -> 403
        $submit = $this->actingAs($teacher)->post(route('guru.penilaian.submit', $assignment));
        $submit->assertStatus(403);

        // Update STS -> 403
        $stsUpdate = $this->actingAs($teacher)->put(route('guru.sts.update', $assignment), [
            'scores' => [],
        ]);
        $stsUpdate->assertStatus(403);
    }
}
