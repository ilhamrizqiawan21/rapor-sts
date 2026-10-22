<?php

namespace Tests\Feature\Kepala;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndRecapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_kepala_can_view_report_card_list(): void
    {
        $kepala = User::factory()->kepala()->create();
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create(['name' => 'Santri Pilihan']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($kepala)->get(route('kepala.rapor.index'));
        $response->assertStatus(200);
        $response->assertSee('Lihat Rapor STS Siswa');
        $response->assertSee('Santri Pilihan');
    }

    public function test_kepala_can_download_student_and_class_report_pdf(): void
    {
        $kepala = User::factory()->kepala()->create();
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create(['name' => 'Santri Satu', 'nisn' => '9988776655']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        // Single student PDF
        $resSingle = $this->actingAs($kepala)->get(route('rapor.student.pdf', [$classroom, $student]));
        $resSingle->assertStatus(200);
        $resSingle->assertHeader('Content-Type', 'application/pdf');

        // All class PDF
        $resAll = $this->actingAs($kepala)->get(route('rapor.classroom.pdf', $classroom));
        $resAll->assertStatus(200);
        $resAll->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_kepala_can_download_excel_and_pdf_recap(): void
    {
        $kepala = User::factory()->kepala()->create();
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);

        Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'level' => 7,
            'name' => 'VII.A',
        ]);

        // Excel recap
        $resExcel = $this->actingAs($kepala)->get(route('kepala.unduh.excel'));
        $resExcel->assertStatus(200);
        $this->assertStringContainsString('Rekap_Nilai_Madrasah_', (string) $resExcel->headers->get('Content-Disposition'));

        // PDF recap
        $resPdf = $this->actingAs($kepala)->get(route('kepala.unduh.pdf'));
        $resPdf->assertStatus(200);
        $resPdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Rekap_Nilai_Madrasah_', (string) $resPdf->headers->get('Content-Disposition'));
    }
}
