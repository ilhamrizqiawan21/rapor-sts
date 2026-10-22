<?php

namespace Tests\Feature\Kepala;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomLock;
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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_non_kepala_cannot_access_dashboard(): void
    {
        $teacher = User::factory()->guru()->create();

        $response = $this->actingAs($teacher)->get(route('kepala.dashboard'));
        $response->assertStatus(403);
    }

    public function test_kepala_can_view_dashboard_with_metrics_and_filter(): void
    {
        $kepala = User::factory()->kepala()->create();
        $teacher1 = User::factory()->guru()->create(['name' => 'Guru Satu']);
        $teacher2 = User::factory()->guru()->create(['name' => 'Guru Dua']);

        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $class7 = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'level' => 7,
            'name' => 'VII.A',
            'homeroom_teacher_id' => $teacher1->id,
        ]);
        $class8 = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'level' => 8,
            'name' => 'VIII.A',
            'homeroom_teacher_id' => $teacher2->id,
        ]);

        $subject = Subject::first();

        // Assignment 1: Terkirim
        $a1 = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $class7->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher1->id,
            'hours_per_week' => 2,
            'weight_summative' => 100,
            'weight_sts' => 0,
            'kktp' => 70,
            'submitted_at' => now(),
            'submitted_by' => $teacher1->id,
        ]);

        // Assignment 2: Belum kirim
        $a2 = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $class8->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher2->id,
            'hours_per_week' => 2,
            'weight_summative' => 100,
            'weight_sts' => 0,
            'kktp' => 70,
            'submitted_at' => null,
        ]);

        // Kunci kelas 7
        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $class7->id,
            'locked_by' => $teacher1->id,
            'locked_at' => now(),
        ]);

        // Akses dashboard tanpa filter
        $response = $this->actingAs($kepala)->get(route('kepala.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard Pemantauan');
        $response->assertSee('50%'); // 1 dari 2 terkirim = 50%
        $response->assertSee('1 dari 2 mapel-kelas');
        $response->assertSee('1 / 2'); // 1 kelas dikunci dari 2 kelas
        $response->assertSee('VII.A');
        $response->assertSee('VIII.A');

        // Akses dengan filter tingkat 7
        $responseFilter7 = $this->actingAs($kepala)->get(route('kepala.dashboard', ['tingkat' => 7]));
        $responseFilter7->assertStatus(200);
        $responseFilter7->assertSee('100%'); // 1 dari 1 di kelas 7 = 100%
        $responseFilter7->assertSee('VII.A');
    }

    public function test_dashboard_query_count_does_not_suffer_from_n_plus_one(): void
    {
        $kepala = User::factory()->kepala()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        // Buat 5 kelas dengan murid dan assignment
        $subject = Subject::first();
        for ($i = 1; $i <= 5; $i++) {
            $teacher = User::factory()->guru()->create();
            $classroom = Classroom::factory()->create([
                'academic_year_id' => $year->id,
                'level' => 7,
                'name' => 'VII.'.$i,
                'homeroom_teacher_id' => $teacher->id,
            ]);

            $student = Student::factory()->create();
            Enrollment::create([
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'student_id' => $student->id,
            ]);

            $assignment = TeachingAssignment::create([
                'period_id' => $period->id,
                'classroom_id' => $classroom->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'hours_per_week' => 2,
                'weight_summative' => 100,
                'weight_sts' => 0,
                'kktp' => 70,
                'submitted_at' => now(),
            ]);

            $scope = LearningScope::create([
                'teaching_assignment_id' => $assignment->id,
                'code' => 'LM1',
                'summary' => 'Materi',
                'kktp' => 70,
                'sequence' => 1,
            ]);

            SummativeScore::create([
                'learning_scope_id' => $scope->id,
                'student_id' => $student->id,
                'score' => 80,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($kepala)->get(route('kepala.dashboard'));
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        // Pastikan jumlah query terbatas dan tidak terjadi N+1 (termasuk auth, middleware, dan layout)
        $this->assertLessThanOrEqual(25, count($queries), 'Dashboard mengalami query N+1: terdeteksi '.count($queries).' query.');
    }
}
