<?php

namespace Tests\Feature\Teacher;

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
use App\Services\Export\RdmExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class RdmExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guru_can_directly_export_summative_scores_to_rdm_format(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student1 = Student::factory()->create(['nisn' => '1111111111', 'nis' => '1001', 'name' => 'Ahmad Santoso']);
        $student2 = Student::factory()->create(['nisn' => '2222222222', 'nis' => '1002', 'name' => 'Budi Pratama']);

        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student1->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student2->id]);

        $scope1 = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'code' => 'LM1',
            'summary' => 'Menjelaskan letak geografis Indonesia',
            'kktp' => 75,
        ]);

        SummativeScore::create(['learning_scope_id' => $scope1->id, 'student_id' => $student1->id, 'score' => 88]);
        SummativeScore::create(['learning_scope_id' => $scope1->id, 'student_id' => $student2->id, 'score' => 92]);

        $response = $this->actingAs($teacher)->get(route('guru.rdm.sumatif.export', $assignment));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertStringContainsString('RDM_Sumatif_', (string) $response->headers->get('Content-Disposition'));

        // Verifikasi isi file hasil ekspor
        $temp = tempnam(sys_get_temp_dir(), 'rdm_sum_').'.xlsx';
        file_put_contents($temp, $response->streamedContent());

        $spreadsheet = IOFactory::load($temp);
        $sheet = $spreadsheet->getSheetByName('SUM 1');
        $this->assertNotNull($sheet);

        $this->assertEquals('Sumatif 1', $sheet->getCell('B2')->getValue());
        $this->assertEquals('Menjelaskan letak geografis Indonesia', $sheet->getCell('B3')->getValue());
        $this->assertTrue($sheet->getProtection()->isProtectionEnabled());

        unlink($temp);
    }

    public function test_guru_can_fill_uploaded_rdm_summative_template_preserving_encrypted_ids_and_protection(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        // Buat siswa dengan NISN yang persis ada di berkas rdm_template_sumatif_v3.1.xlsx (baris 7 & 8)
        $student1 = Student::factory()->create([
            'nisn' => '0101477189',
            'nis' => '230007',
            'name' => 'AIDIL ADHA MULYANA',
        ]);
        $student2 = Student::factory()->create([
            'nisn' => '0101091072',
            'nis' => '230018',
            'name' => 'AZQI MUHAMMAD RHAZEEQ',
        ]);

        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student1->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student2->id]);

        $scope1 = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'code' => 'LM1',
            'summary' => 'Memahami kondisi alam benua Asia',
            'kktp' => 75,
        ]);

        SummativeScore::create(['learning_scope_id' => $scope1->id, 'student_id' => $student1->id, 'score' => 89]);
        SummativeScore::create(['learning_scope_id' => $scope1->id, 'student_id' => $student2->id, 'score' => 95]);

        $templateSourcePath = base_path('reference/rdm-template/rdm_template_sumatif_v3.1.xlsx');
        $uploadedFile = new UploadedFile(
            path: $templateSourcePath,
            originalName: 'template_sumatif_resmi_rdm.xlsx',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            error: null,
            test: true
        );

        $response = $this->actingAs($teacher)->post(route('guru.rdm.sumatif.fill', $assignment), [
            'template_file' => $uploadedFile,
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('RDM_Sumatif_Terisi_', (string) $response->headers->get('Content-Disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'rdm_fill_').'.xlsx';
        file_put_contents($temp, $response->streamedContent());

        $spreadsheet = IOFactory::load($temp);
        $sheet = $spreadsheet->getSheetByName('SUM 1');
        $this->assertNotNull($sheet);

        // Nilai terisi di Kolom F
        $this->assertEquals(89, (float) $sheet->getCell('F7')->getValue());
        $this->assertEquals(95, (float) $sheet->getCell('F8')->getValue());

        // Token ID Siswa internal RDM di Kolom B TIDAK BERUBAH / TETAP UTUH
        $this->assertEquals('QU51ZVFuRDgzSnVOTG9qaVFobndodz09OmJUUmtjalJ6TkdoaU1YTTBaREJ1T1E9PQ', $sheet->getCell('B7')->getValue());
        $this->assertEquals('YUY1KzBQSVVWdXJpb2hTL1BtRlJyUT09OmJUUmtjalJ6TkdoaU1YTTBaREJ1T1E9PQ', $sheet->getCell('B8')->getValue());

        // Proteksi lembar dan password tetap utuh
        $this->assertTrue($sheet->getProtection()->isProtectionEnabled());
        $this->assertEquals('8619', $sheet->getProtection()->getPassword());

        unlink($temp);
    }

    public function test_guru_can_directly_export_sts_scores_to_rdm_format(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create(['nisn' => '3333333333', 'nis' => '1003', 'name' => 'Citra Lestari']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $student->id, 'score' => 90]);

        $response = $this->actingAs($teacher)->get(route('guru.rdm.sts.export', $assignment));
        $response->assertStatus(200);
        $this->assertStringContainsString('RDM_STS_', (string) $response->headers->get('Content-Disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'rdm_sts_').'.xlsx';
        file_put_contents($temp, $response->streamedContent());

        $spreadsheet = IOFactory::load($temp);
        $sheet = $spreadsheet->getSheet(0);

        $this->assertEquals('STS 1', $sheet->getCell('B2')->getValue());
        $this->assertTrue($sheet->getProtection()->isProtectionEnabled());

        unlink($temp);
    }

    public function test_guru_can_fill_uploaded_rdm_pat_template_preserving_encrypted_ids(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VIII.C']);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        // Siswa dari berkas rdm_template_pat_v3.1.xlsx (baris 7 & 8)
        $student1 = Student::factory()->create(['nisn' => '0095077208', 'nis' => '220002', 'name' => 'AIDIL MULYANA']);
        $student2 = Student::factory()->create(['nisn' => '0091828140', 'nis' => '220007', 'name' => 'ALGI APRIANSYAH']);

        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student1->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student2->id]);

        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $student1->id, 'score' => 88]);
        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $student2->id, 'score' => 87]);

        $templateSourcePath = base_path('reference/rdm-template/rdm_template_pat_v3.1.xlsx');
        $uploadedFile = new UploadedFile(
            path: $templateSourcePath,
            originalName: 'template_pat_rdm.xlsx',
            mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            error: null,
            test: true
        );

        $response = $this->actingAs($teacher)->post(route('guru.rdm.sts.fill', $assignment), [
            'template_file' => $uploadedFile,
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('RDM_STS_Terisi_', (string) $response->headers->get('Content-Disposition'));

        $temp = tempnam(sys_get_temp_dir(), 'rdm_sts_fill_').'.xlsx';
        file_put_contents($temp, $response->streamedContent());

        $spreadsheet = IOFactory::load($temp);
        $sheet = $spreadsheet->getSheet(0);

        $this->assertEquals(88, (float) $sheet->getCell('F7')->getValue());
        $this->assertEquals(87, (float) $sheet->getCell('F8')->getValue());
        $this->assertEquals('MzViWGxKeXBpdGRLenhHMXY3Mk55UT09OmJUUmtjalJ6TkdoaU1YTTBaREJ1T1E9PQ', $sheet->getCell('B7')->getValue());
        $this->assertEquals('cENBbDd0ZElwS0ZXWEdtWjZHSEU1QT09OmJUUmtjalJ6TkdoaU1YTTBaREJ1T1E9PQ', $sheet->getCell('B8')->getValue());

        unlink($temp);
    }

    public function test_unauthorized_teacher_cannot_export_another_teachers_assignment(): void
    {
        $teacherOwner = User::factory()->guru()->create();
        $otherTeacher = User::factory()->guru()->create();

        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacherOwner->id,
        ]);

        $resGet = $this->actingAs($otherTeacher)->get(route('guru.rdm.sumatif.export', $assignment));
        $resGet->assertStatus(403);

        $resPost = $this->actingAs($otherTeacher)->post(route('guru.rdm.sumatif.fill', $assignment), []);
        $resPost->assertStatus(403);
    }

    public function test_validation_rejects_non_xlsx_file_on_fill(): void
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

        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($teacher)->post(route('guru.rdm.sumatif.fill', $assignment), [
            'template_file' => $invalidFile,
        ]);

        $response->assertSessionHasErrors(['template_file']);
    }

    public function test_rdm_exporter_unit_fills_multiple_sheets_and_matches_filled_example(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $subject = Subject::first();

        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student1 = Student::factory()->create(['nisn' => '0101477189', 'nis' => '230007', 'name' => 'AIDIL ADHA MULYANA']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student1->id]);

        $scope1 = LearningScope::create(['teaching_assignment_id' => $assignment->id, 'sequence' => 1, 'code' => 'LM1', 'summary' => 'TP 1 Geografi', 'kktp' => 75]);
        $scope2 = LearningScope::create(['teaching_assignment_id' => $assignment->id, 'sequence' => 2, 'code' => 'LM2', 'summary' => 'TP 2 Sosiologi', 'kktp' => 75]);
        $scope3 = LearningScope::create(['teaching_assignment_id' => $assignment->id, 'sequence' => 3, 'code' => 'LM3', 'summary' => 'TP 3 Ekonomi', 'kktp' => 75]);

        SummativeScore::create(['learning_scope_id' => $scope1->id, 'student_id' => $student1->id, 'score' => 80]);
        SummativeScore::create(['learning_scope_id' => $scope2->id, 'student_id' => $student1->id, 'score' => 85]);
        SummativeScore::create(['learning_scope_id' => $scope3->id, 'student_id' => $student1->id, 'score' => 90]);

        $exporter = app(RdmExporter::class);
        $spreadsheet = $exporter->exportSummative($assignment);

        // Sheet SUM 1
        $sh1 = $spreadsheet->getSheetByName('SUM 1');
        $this->assertNotNull($sh1);
        $this->assertEquals('Sumatif 1', $sh1->getCell('B2')->getValue());
        $this->assertEquals('TP 1 Geografi', $sh1->getCell('B3')->getValue());
        $this->assertEquals(80, (float) $sh1->getCell('F7')->getValue());
        $this->assertEquals('QU51ZVFuRDgzSnVOTG9qaVFobndodz09OmJUUmtjalJ6TkdoaU1YTTBaREJ1T1E9PQ', $sh1->getCell('B7')->getValue());
        $this->assertTrue($sh1->getProtection()->isProtectionEnabled());

        // Sheet SUM 2
        $sh2 = $spreadsheet->getSheetByName('SUM 2');
        $this->assertNotNull($sh2);
        $this->assertEquals('Sumatif 2', $sh2->getCell('B2')->getValue());
        $this->assertEquals('TP 2 Sosiologi', $sh2->getCell('B3')->getValue());
        $this->assertEquals(85, (float) $sh2->getCell('F7')->getValue());
        $this->assertEquals('QU51ZVFuRDgzSnVOTG9qaVFobndodz09OmJUUmtjalJ6TkdoaU1YTTBaREJ1T1E9PQ', $sh2->getCell('B7')->getValue());
        $this->assertTrue($sh2->getProtection()->isProtectionEnabled());

        // Sheet SUM 3
        $sh3 = $spreadsheet->getSheetByName('SUM 3');
        $this->assertNotNull($sh3);
        $this->assertEquals('Sumatif 3', $sh3->getCell('B2')->getValue());
        $this->assertEquals('TP 3 Ekonomi', $sh3->getCell('B3')->getValue());
        $this->assertEquals(90, (float) $sh3->getCell('F7')->getValue());
        $this->assertEquals('QU51ZVFuRDgzSnVOTG9qaVFobndodz09OmJUUmtjalJ6TkdoaU1YTTBaREJ1T1E9PQ', $sh3->getCell('B7')->getValue());
        $this->assertTrue($sh3->getProtection()->isProtectionEnabled());
    }
}
