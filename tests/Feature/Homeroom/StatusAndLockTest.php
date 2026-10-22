<?php

namespace Tests\Feature\Homeroom;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomLock;
use App\Models\Period;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusAndLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_status_nilai_shows_progress_and_table(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $subject = Subject::first();
        TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'weight_summative' => 50,
            'weight_sts' => 50,
            'kktp' => 70,
        ]);

        $response = $this->actingAs($teacher)->get(route('wali.status.index'));
        $response->assertStatus(200);
        $response->assertSee('Status Nilai Rapor STS — VII.A');
        $response->assertSee('Sumatif Lingkup Materi');
        $response->assertSee('Nilai STS');
    }

    public function test_cannot_lock_classroom_if_not_all_subjects_submitted(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $subject = Subject::first();
        TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'weight_summative' => 50,
            'weight_sts' => 50,
            'kktp' => 70,
            'submitted_at' => null, // Belum kirim
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.status.lock', $classroom));
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Tidak dapat mengunci nilai kelas karena masih ada mata pelajaran yang belum terkirim.');

        $this->assertDatabaseMissing('classroom_locks', [
            'classroom_id' => $classroom->id,
            'period_id' => $period->id,
        ]);
    }

    public function test_can_lock_classroom_when_all_subjects_submitted(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $subject = Subject::first();
        TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'weight_summative' => 50,
            'weight_sts' => 50,
            'kktp' => 70,
            'submitted_at' => now(), // Terkirim
            'submitted_by' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.status.lock', $classroom));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('classroom_locks', [
            'classroom_id' => $classroom->id,
            'period_id' => $period->id,
        ]);
    }

    public function test_can_unlock_classroom(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'locked_by' => $teacher->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.status.unlock', $classroom));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('classroom_locks', [
            'classroom_id' => $classroom->id,
            'period_id' => $period->id,
        ]);
    }

    public function test_can_unsubmit_assignment_when_not_locked(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $subject = Subject::first();
        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'weight_summative' => 50,
            'weight_sts' => 50,
            'kktp' => 70,
            'submitted_at' => now(),
            'submitted_by' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.status.unsubmit', $assignment));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('teaching_assignments', [
            'id' => $assignment->id,
            'submitted_at' => null,
            'submitted_by' => null,
        ]);
    }

    public function test_cannot_unsubmit_assignment_when_locked(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'locked_by' => $teacher->id,
            'locked_at' => now(),
        ]);

        $subject = Subject::first();
        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'weight_summative' => 50,
            'weight_sts' => 50,
            'kktp' => 70,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.status.unsubmit', $assignment));
        $response->assertStatus(403);
    }
}
