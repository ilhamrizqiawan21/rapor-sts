<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_assign_teacher_to_subject_in_classroom(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'semester' => 1, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $teacher = User::factory()->guru()->create();
        $subject = Subject::where('code', 'FIK')->first();

        $response = $this->actingAs($operator)->post(route('operator.mengajar.store'), [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'kktp' => 75.00,
            'weight_summative' => 60.00,
            'weight_sts' => 40.00,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('teaching_assignments', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'kktp' => 75.00,
        ]);
    }

    public function test_mengajar_page_displays_kktp_column_and_default_value(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'semester' => 1, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'level' => 7]);

        $response = $this->actingAs($operator)->get(route('operator.mengajar.index', ['classroom_id' => $classroom->id]));

        $response->assertStatus(200);
        $response->assertSee('KKTP / KKM');
        $response->assertSee('name="kktp"', false);
    }

    public function test_copy_teaching_assignments_does_not_overwrite_existing_assignments(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        $p1 = Period::create(['academic_year_id' => $year->id, 'semester' => 1, 'is_active' => false]);
        $p2 = Period::create(['academic_year_id' => $year->id, 'semester' => 2, 'is_active' => true]);

        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $teacher1 = User::factory()->guru()->create(['name' => 'Guru Lama']);
        $teacher2 = User::factory()->guru()->create(['name' => 'Guru Baru']);
        $subjectFik = Subject::where('code', 'FIK')->first();
        $subjectBar = Subject::where('code', 'BAR')->first();

        // Periode 1 ada Fikih (Guru Lama) & Bahasa Arab (Guru Lama)
        TeachingAssignment::create([
            'period_id' => $p1->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subjectFik->id,
            'teacher_id' => $teacher1->id,
            'hours_per_week' => 2,
        ]);
        TeachingAssignment::create([
            'period_id' => $p1->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subjectBar->id,
            'teacher_id' => $teacher1->id,
            'hours_per_week' => 3,
        ]);

        // Periode 2 SUDAH ada Fikih (Guru Baru)
        TeachingAssignment::create([
            'period_id' => $p2->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subjectFik->id,
            'teacher_id' => $teacher2->id,
            'hours_per_week' => 2,
        ]);

        // Salin dari P1 ke P2
        $response = $this->actingAs($operator)->post(route('operator.mengajar.copy'), [
            'source_period_id' => $p1->id,
            'target_period_id' => $p2->id,
        ]);

        $response->assertRedirect();

        // Mapel Fikih di P2 HARUS TETAP Guru Baru (tidak ditimpa)
        $fikAssignment = TeachingAssignment::where('period_id', $p2->id)
            ->where('classroom_id', $classroom->id)
            ->where('subject_id', $subjectFik->id)
            ->first();
        $this->assertSame($teacher2->id, $fikAssignment->teacher_id);

        // Mapel Bahasa Arab di P2 berhasil disalin
        $this->assertDatabaseHas('teaching_assignments', [
            'period_id' => $p2->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subjectBar->id,
            'teacher_id' => $teacher1->id,
        ]);
    }

    public function test_operator_can_set_kktp_default(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        $subject = Subject::where('code', 'MTK')->first();

        $response = $this->actingAs($operator)->post(route('operator.kktp.store'), [
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'level' => 7,
            'kktp' => 78.50,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subject_kktp_defaults', [
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'level' => 7,
            'kktp' => 78.50,
        ]);
    }
}
