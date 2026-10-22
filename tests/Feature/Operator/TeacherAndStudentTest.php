<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\StsScore;
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

class TeacherAndStudentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_download_teacher_template(): void
    {
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->get(route('operator.teachers.template'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_operator_can_import_teachers_from_excel(): void
    {
        $operator = User::where('username', 'admin')->first();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('guru');
        $sheet->setCellValue('A3', 'NIK/NUPTK');
        $sheet->setCellValue('B3', 'NIP');
        $sheet->setCellValue('C3', 'Nama');
        $sheet->setCellValue('D3', 'L/P');
        $sheet->setCellValue('E3', 'Password');

        $sheet->setCellValueExplicit('A4', '320499990001', DataType::TYPE_STRING);
        $sheet->setCellValue('B4', '198501012010011001');
        $sheet->setCellValue('C4', 'Ustadz Zulkifli, S.Pd.I');
        $sheet->setCellValue('D4', 'L');
        $sheet->setCellValue('E4', 'secret123');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_teacher_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'guru.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($operator)->post(route('operator.teachers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('operator.teachers.index'));
        $this->assertDatabaseHas('users', [
            'username' => '320499990001',
            'name' => 'Ustadz Zulkifli, S.Pd.I',
        ]);

        @unlink($tempPath);
    }

    public function test_teacher_import_rolls_back_if_too_many_errors(): void
    {
        $operator = User::where('username', 'admin')->first();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('guru');

        // Buat 5 baris, tapi 3 di antaranya tanpa NIK (>20% error)
        for ($i = 4; $i <= 8; $i++) {
            if ($i <= 6) {
                $sheet->setCellValue('A'.$i, ''); // Error: NIK kosong
                $sheet->setCellValue('C'.$i, 'Guru Tanpa NIK '.$i);
            } else {
                $sheet->setCellValue('A'.$i, '32049999000'.$i);
                $sheet->setCellValue('C'.$i, 'Guru Valid '.$i);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_err_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'guru_error.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($operator)->post(route('operator.teachers.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('users', ['username' => '320499990007']);

        @unlink($tempPath);
    }

    public function test_operator_can_import_students_and_automatically_enrolled_to_classroom(): void
    {
        $operator = User::where('username', 'admin')->first();
        $classroom = Classroom::factory()->create(['name' => 'VII.A']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('siswa');
        $sheet->setCellValue('A3', 'NIS');
        $sheet->setCellValue('B3', 'NISN');
        $sheet->setCellValue('C3', 'Nama');
        $sheet->setCellValue('D3', 'L/P');
        $sheet->setCellValue('E3', 'Tempat Lahir');
        $sheet->setCellValue('F3', 'Tanggal Lahir');

        $sheet->setCellValueExplicit('A4', '26001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B4', '0112345678', DataType::TYPE_STRING);
        $sheet->setCellValue('C4', 'Ahmad Ridwan');
        $sheet->setCellValue('D4', 'L');
        $sheet->setCellValue('E4', 'Bandung');
        $sheet->setCellValue('F4', '2012-08-17');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_student_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $file = new UploadedFile($tempPath, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($operator)->post(route('operator.students.import'), [
            'classroom_id' => $classroom->id,
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'nisn' => '0112345678',
            'name' => 'AHMAD RIDWAN',
        ]);

        $student = Student::where('nisn', '0112345678')->first();
        $this->assertDatabaseHas('enrollments', [
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        @unlink($tempPath);
    }

    public function test_operator_can_move_student_to_different_classroom(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        $classA = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $classB = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.B']);

        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classA->id,
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($operator)->post(route('operator.students.move', $student), [
            'target_classroom_id' => $classB->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'academic_year_id' => $year->id,
            'classroom_id' => $classB->id,
            'student_id' => $student->id,
        ]);

        // Pastikan tidak ada duplikasi enrollment pada tahun yang sama
        $this->assertCount(1, $student->fresh()->enrollments);
    }

    public function test_operator_can_update_student_data(): void
    {
        $operator = User::where('username', 'admin')->first();
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create([
            'nisn' => '0098765432',
            'name' => 'Budi Santoso',
        ]);

        $response = $this->actingAs($operator)->put(route('operator.students.update', $student), [
            'nis' => '12345',
            'nisn' => '0098765432',
            'name' => 'Budi Santoso Update',
            'gender' => 'L',
            'birth_place' => 'Jakarta',
            'birth_date' => '2011-05-10',
            'classroom_id' => $classroom->id,
            'address' => 'Jl. Merdeka No. 10',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Budi Santoso Update',
            'birth_place' => 'Jakarta',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
        ]);
    }

    public function test_operator_can_delete_student_without_scores(): void
    {
        $operator = User::where('username', 'admin')->first();
        $student = Student::factory()->create();
        $classroom = Classroom::factory()->create();
        $enrollment = Enrollment::create([
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.students.destroy', $student));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_operator_cannot_delete_student_with_existing_scores(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'semester' => 1, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $teacher = User::factory()->guru()->create();
        $subject = Subject::first();
        $assignment = TeachingAssignment::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        // Berikan nilai STS kepada siswa
        StsScore::create([
            'period_id' => $period->id,
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'score' => 85.00,
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.students.destroy', $student));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Siswa tidak boleh terhapus dari database
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }
}
