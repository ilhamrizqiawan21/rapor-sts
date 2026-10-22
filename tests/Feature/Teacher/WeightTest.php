<?php

namespace Tests\Feature\Teacher;

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

class WeightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_teacher_can_view_and_update_assessment_weights(): void
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
            'hours_per_week' => 2,
            'weight_summative' => 50.00,
            'weight_sts' => 50.00,
            'kktp' => 70.00,
        ]);

        $response = $this->actingAs($teacher)->get(route('guru.bobot.edit', $assignment));
        $response->assertStatus(200);
        $response->assertSee('Bobot · '.$subject->name);

        $updateResponse = $this->actingAs($teacher)->put(route('guru.bobot.update', $assignment), [
            'hours_per_week' => 4,
            'weight_summative' => 60.00,
            'weight_sts' => 40.00,
        ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('teaching_assignments', [
            'id' => $assignment->id,
            'hours_per_week' => 4,
            'weight_summative' => 60.00,
            'weight_sts' => 40.00,
        ]);
    }

    public function test_zero_weights_are_rejected_by_validation(): void
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

        $response = $this->actingAs($teacher)->put(route('guru.bobot.update', $assignment), [
            'hours_per_week' => 2,
            'weight_summative' => 0,
            'weight_sts' => 0,
        ]);

        $response->assertSessionHasErrors('weight_summative');
    }

    public function test_updating_weight_is_forbidden_when_submitted_or_locked(): void
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
            'submitted_at' => now(), // Terkirim
        ]);

        $response = $this->actingAs($teacher)->put(route('guru.bobot.update', $assignment), [
            'hours_per_week' => 2,
            'weight_summative' => 50,
            'weight_sts' => 50,
        ]);

        $response->assertStatus(403);

        // Buka submitted tapi kunci kelas
        $assignment->update(['submitted_at' => null]);
        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'locked_at' => now(),
        ]);

        $lockedResponse = $this->actingAs($teacher)->put(route('guru.bobot.update', $assignment), [
            'hours_per_week' => 2,
            'weight_summative' => 50,
            'weight_sts' => 50,
        ]);

        $lockedResponse->assertStatus(403);
    }
}
