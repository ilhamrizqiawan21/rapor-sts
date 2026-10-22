<?php

namespace Tests\Feature\Kepala;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadOnlyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_headmaster_is_denied_from_all_write_actions(): void
    {
        $kepala = User::factory()->kepala()->create();
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
            'weight_summative' => 100,
            'weight_sts' => 0,
            'kktp' => 70,
        ]);

        // 1. Tulis Operator Siswa
        $res1 = $this->actingAs($kepala)->post(route('operator.students.store'), [
            'name' => 'Siswa Baru',
            'nisn' => '1234567890',
            'gender' => 'L',
        ]);
        $res1->assertStatus(403);

        // 2. Tulis Operator Guru
        $res2 = $this->actingAs($kepala)->post(route('operator.teachers.store'), [
            'name' => 'Guru Baru',
            'username' => 'gurubaru',
        ]);
        $res2->assertStatus(403);

        // 3. Tulis Pengaturan Cetak
        $res3 = $this->actingAs($kepala)->post(route('operator.print-settings.update'), [
            'report_title' => 'RAPOR MODIFIKASI',
        ]);
        $res3->assertStatus(403);

        // 4. Kirim Nilai Guru
        $res4 = $this->actingAs($kepala)->post(route('guru.penilaian.submit', $assignment));
        $res4->assertStatus(403);

        // 5. Simpan Kehadiran Wali Kelas
        $res5 = $this->actingAs($kepala)->post(route('wali.kehadiran.update'), [
            'records' => [],
        ]);
        $res5->assertStatus(403);

        // 6. Kunci Kelas
        $res6 = $this->actingAs($kepala)->post(route('wali.status.lock', $classroom));
        $res6->assertStatus(403);

        // 7. Buka Kunci Kelas
        $res7 = $this->actingAs($kepala)->post(route('wali.status.unlock', $classroom));
        $res7->assertStatus(403);

        // 8. Batal Kirim Nilai
        $res8 = $this->actingAs($kepala)->post(route('wali.status.unsubmit', $assignment));
        $res8->assertStatus(403);
    }
}
