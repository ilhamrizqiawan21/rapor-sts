<?php

namespace Tests\Feature\Report;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\HomeroomRecord;
use App\Models\LearningScope;
use App\Models\Period;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SummativeScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Report\ReportCardBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportCardAndLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_report_card_builder_prepares_accurate_data(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
            'phase' => 'D',
        ]);

        $student = Student::factory()->create(['name' => 'Budi Santoso', 'nisn' => '1234567890']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        HomeroomRecord::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'sick' => 1,
            'permit' => 2,
            'absent' => 0,
            'note' => 'Belajar lebih giat.',
        ]);

        // Cari sub-mapel PAI dan mapel mandiri
        $qh = Subject::where('code', 'QH')->first();
        $mtk = Subject::where('code', 'MTK')->first();

        $assignmentQh = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $qh->id,
            'teacher_id' => $teacher->id,
            'hours_per_week' => 2,
            'weight_summative' => 100,
            'weight_sts' => 0,
            'kktp' => 70,
        ]);

        $scope = LearningScope::create([
            'teaching_assignment_id' => $assignmentQh->id,
            'code' => 'LM1',
            'summary' => 'membaca Al-Quran',
            'kktp' => 70,
            'sequence' => 1,
        ]);

        SummativeScore::create([
            'learning_scope_id' => $scope->id,
            'student_id' => $student->id,
            'score' => 85,
        ]);

        $builder = new ReportCardBuilder;
        $data = $builder->buildClassReportData($classroom, $period, $student);

        $this->assertDatabaseMissing('print_settings', ['period_id' => $period->id]);

        $this->assertTrue($data['isDraft']);
        $this->assertEquals('DRAFT', $data['watermarkText']);
        $this->assertCount(1, $data['studentsData']);

        $studentData = $data['studentsData']->first();
        $this->assertEquals('Budi Santoso', $studentData['student']->name);
        $this->assertEquals(1, $studentData['sick']);
        $this->assertEquals(2, $studentData['permit']);
        $this->assertEquals('Belajar lebih giat.', $studentData['note']);

        // Pastikan ada header PAI dan baris sub-mapel A. Al Qur'an Hadis
        $subjectRows = collect($studentData['subjectRows']);
        $parentRow = $subjectRows->firstWhere('is_parent', true);
        $this->assertNotNull($parentRow);
        $this->assertEquals('Pendidikan Agama Islam', $parentRow['subject_name']);

        $childRow = $subjectRows->firstWhere('letter', 'A.');
        $this->assertNotNull($childRow);
        $this->assertEquals(85, $childRow['score']);
    }

    public function test_single_student_report_card_pdf_opens_inline(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create(['name' => 'Ahmad Siswa', 'nisn' => '0012345678']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        $response = $this->actingAs($teacher)->get(route('rapor.student.pdf', [$classroom, $student]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Rapor_STS_VII.A_Ahmad_Siswa_0012345678.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_all_students_report_card_pdf_opens_inline(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create(['name' => 'Siswa Satu', 'nisn' => '1111111111']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        $response = $this->actingAs($teacher)->get(route('rapor.classroom.pdf', $classroom));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Rapor_STS_VII.A_semua.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_ledger_pdf_and_excel_download(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create(['name' => '=1+1', 'nisn' => '1111111111']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        // PDF
        $responsePdf = $this->actingAs($teacher)->get(route('rapor.leger.pdf', $classroom));
        $responsePdf->assertStatus(200);
        $responsePdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('Leger_STS_VII.A.pdf', (string) $responsePdf->headers->get('Content-Disposition'));

        // Excel
        $responseExcel = $this->actingAs($teacher)->get(route('rapor.leger.excel', $classroom));
        $responseExcel->assertStatus(200);
        $this->assertStringContainsString('Leger_STS_VII.A.xlsx', (string) $responseExcel->headers->get('Content-Disposition'));

        // Verifikasi struktur berkas Excel Leger multi-sheet
        $tempExcel = tempnam(sys_get_temp_dir(), 'leger_test_').'.xlsx';
        file_put_contents($tempExcel, $responseExcel->streamedContent());
        $loadedExcel = IOFactory::load($tempExcel);

        $this->assertTrue($loadedExcel->sheetNameExists('Pengetahuan'));
        $this->assertTrue($loadedExcel->sheetNameExists('Keterampilan'));

        $sheetP = $loadedExcel->getSheetByName('Pengetahuan');
        $this->assertEquals('LEGGER NILAI PENGETAHUAN', $sheetP->getCell('A1')->getValue());
        $this->assertEquals('No', $sheetP->getCell('A6')->getValue());
        $this->assertEquals('NIS', $sheetP->getCell('B6')->getValue());
        $this->assertEquals('Nisn', $sheetP->getCell('C6')->getValue());
        $this->assertEquals('Nama', $sheetP->getCell('D6')->getValue());
        $this->assertEquals('JK', $sheetP->getCell('E6')->getValue());
        $this->assertSame('=1+1', $sheetP->getCell('D8')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheetP->getCell('D8')->getDataType());

        $sheetK = $loadedExcel->getSheetByName('Keterampilan');
        $this->assertEquals('LEGGER NILAI KETERAMPILAN', $sheetK->getCell('A1')->getValue());

        unlink($tempExcel);
    }

    public function test_unauthorized_teacher_cannot_access_report_card(): void
    {
        $teacher1 = User::factory()->guru()->create();
        $teacher2 = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher1->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create();
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        // Teacher 2 bukan wali kelas ini -> 403
        $response = $this->actingAs($teacher2)->get(route('rapor.student.pdf', [$classroom, $student]));
        $response->assertStatus(403);

        // Operator boleh -> 200
        $operator = User::factory()->operator()->create();
        $responseOp = $this->actingAs($operator)->get(route('rapor.student.pdf', [$classroom, $student]));
        $responseOp->assertStatus(200);
    }

    public function test_single_report_card_for_student_outside_classroom_returns_404(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII.A',
            'homeroom_teacher_id' => $teacher->id,
        ]);
        $otherClassroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'VII.B',
        ]);

        $outsider = Student::factory()->create();
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $otherClassroom->id, 'student_id' => $outsider->id]);

        $response = $this->actingAs($teacher)->get(route('rapor.student.pdf', [$classroom, $outsider]));

        $response->assertNotFound();
    }
}
