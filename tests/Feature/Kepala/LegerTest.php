<?php

namespace Tests\Feature\Kepala;

use App\Models\AcademicYear;
use App\Models\Classroom;
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

class LegerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_non_kepala_cannot_access_leger(): void
    {
        $teacher = User::factory()->guru()->create();

        $response = $this->actingAs($teacher)->get(route('kepala.leger.index'));
        $response->assertStatus(403);
    }

    public function test_kepala_can_view_leger_with_different_view_types(): void
    {
        $kepala = User::factory()->kepala()->create();
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'level' => 7,
            'name' => 'VII.A',
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create(['name' => 'Ahmad Santoso']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
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
            'kktp' => 75,
            'submitted_at' => now(),
        ]);

        $scope = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'code' => 'LM1',
            'summary' => 'Materi',
            'kktp' => 75,
            'sequence' => 1,
        ]);

        SummativeScore::create([
            'learning_scope_id' => $scope->id,
            'student_id' => $student->id,
            'score' => 60, // Di bawah KKTP 75
        ]);

        StsScore::create([
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'score' => 70, // Di bawah KKTP 75
        ]);

        // 1. Tampilan default (Nilai Rapor STS)
        $resRapor = $this->actingAs($kepala)->get(route('kepala.leger.index', [
            'classroom_id' => $classroom->id,
            'tampilkan' => 'rapor',
        ]));
        $resRapor->assertStatus(200);
        $resRapor->assertSee('Ahmad Santoso');
        $resRapor->assertSee('class="lo"', false); // skor 65 < KKTP 75 ditandai class="lo"
        $resRapor->assertSee('65'); // (60 + 70)/2 = 65

        // 2. Tampilan Rerata Sumatif LM
        $resSumatif = $this->actingAs($kepala)->get(route('kepala.leger.index', [
            'classroom_id' => $classroom->id,
            'tampilkan' => 'sumatif',
        ]));
        $resSumatif->assertStatus(200);
        $resSumatif->assertSee('60');

        // 3. Tampilan Nilai Tes STS
        $resSts = $this->actingAs($kepala)->get(route('kepala.leger.index', [
            'classroom_id' => $classroom->id,
            'tampilkan' => 'sts',
        ]));
        $resSts->assertStatus(200);
        $resSts->assertSee('70');
    }

    public function test_unsubmitted_assignment_shows_placeholder_in_leger(): void
    {
        $kepala = User::factory()->kepala()->create();
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'level' => 7,
            'name' => 'VII.A',
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $subject = Subject::first();
        TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'weight_summative' => 100,
            'weight_sts' => 0,
            'kktp' => 70,
            'submitted_at' => null, // Belum kirim
        ]);

        $response = $this->actingAs($kepala)->get(route('kepala.leger.index', [
            'classroom_id' => $classroom->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee('–');
    }
}
