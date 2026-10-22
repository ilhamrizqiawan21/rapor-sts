<?php

namespace Tests\Feature\Kepala;

use App\Models\AcademicYear;
use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_non_kepala_cannot_access_teacher_performance(): void
    {
        $teacher = User::factory()->guru()->create();

        $response = $this->actingAs($teacher)->get(route('kepala.kinerja.index'));
        $response->assertStatus(403);
    }

    public function test_kepala_can_view_teacher_performance(): void
    {
        $kepala = User::factory()->kepala()->create();
        $teacher = User::factory()->guru()->create(['name' => 'Ustadz Fulan, S.Pd', 'nip' => '198501012010011002']);
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII.A',
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
            'submitted_at' => now(),
            'submitted_by' => $teacher->id,
        ]);

        ActivityLog::create([
            'user_id' => $teacher->id,
            'action' => 'login',
            'description' => 'Login ke sistem',
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($kepala)->get(route('kepala.kinerja.index'));
        $response->assertStatus(200);
        $response->assertSee('Kinerja Input Nilai Guru');
        $response->assertSee('Ustadz Fulan, S.Pd');
        $response->assertSee('198501012010011002');
        $response->assertSee('100%');
        $response->assertSee('1/1 mapel-kelas');
    }
}
