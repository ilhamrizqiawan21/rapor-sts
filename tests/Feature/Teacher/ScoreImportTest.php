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
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ScoreImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_teacher_can_download_summative_template(): void
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

        $response = $this->actingAs($teacher)->get(route('guru.sumatif.template', $assignment));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_teacher_can_import_summative_scores_from_excel(): void
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
            'kktp' => 70.00,
        ]);

        $student = Student::factory()->create(['nisn' => '0011223344']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LM1');
        $sheet->setCellValue('A1', 'Ringkasan LM:');
        $sheet->setCellValue('B1', 'menganalisis tata cara bersuci');
        $sheet->setCellValue('A2', 'KKTP LM:');
        $sheet->setCellValue('B2', 70);

        $sheet->setCellValue('A3', 'ID');
        $sheet->setCellValue('B3', 'NISN');
        $sheet->setCellValue('C3', 'Nama');
        $sheet->setCellValue('D3', 'L/P');
        $sheet->setCellValue('E3', 'Nilai');

        $sheet->setCellValue('A4', $student->id);
        $sheet->setCellValueExplicit('B4', $student->nisn, DataType::TYPE_STRING);
        $sheet->setCellValue('C4', $student->name);
        $sheet->setCellValue('D4', $student->gender);
        $sheet->setCellValue('E4', 85);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_sumatif_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'sumatif.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($teacher)->post(route('guru.sumatif.import', $assignment), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('guru.sumatif.index', $assignment));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('learning_scopes', [
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'menganalisis tata cara bersuci',
        ]);

        $this->assertDatabaseHas('summative_scores', [
            'student_id' => $student->id,
            'score' => 85.00,
        ]);

        @unlink($tempPath);
    }

    public function test_teacher_can_download_and_import_sts_scores(): void
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
            'kktp' => 70.00,
        ]);

        $student = Student::factory()->create(['nisn' => '0099887766']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        // Download STS template
        $dlResponse = $this->actingAs($teacher)->get(route('guru.sts.template', $assignment));
        $dlResponse->assertStatus(200);

        // Buat file STS Excel
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('STS');

        $sheet->setCellValue('A1', 'TEMPLATE NILAI STS');
        $sheet->setCellValue('A2', 'Keterangan');
        $sheet->setCellValue('A3', 'ID');
        $sheet->setCellValue('B3', 'NISN');
        $sheet->setCellValue('C3', 'Nama');
        $sheet->setCellValue('D3', 'L/P');
        $sheet->setCellValue('E3', 'Nilai STS');

        $sheet->setCellValue('A4', $student->id);
        $sheet->setCellValueExplicit('B4', $student->nisn, DataType::TYPE_STRING);
        $sheet->setCellValue('C4', $student->name);
        $sheet->setCellValue('D4', $student->gender);
        $sheet->setCellValue('E4', 88);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_sts_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'sts.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($teacher)->post(route('guru.sts.import', $assignment), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('guru.sts.index', $assignment));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sts_scores', [
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'score' => 88.00,
        ]);

        @unlink($tempPath);
    }
}
