<?php

namespace Tests\Feature\Operator;

use App\Enums\Role;
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

    public function test_student_import_rejects_missing_birth_date_instead_of_inventing_one(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $classroom = Classroom::factory()->create();
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('siswa');
        $sheet->setCellValue('B4', '0999999999');
        $sheet->setCellValue('C4', 'Siswa Uji');
        $sheet->setCellValue('D4', 'L');
        $sheet->setCellValue('E4', 'Bandung');
        $path = tempnam(sys_get_temp_dir(), 'student_missing_date_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        try {
            $this->actingAs($operator)->post(route('operator.students.import'), [
                'classroom_id' => $classroom->id,
                'file' => new UploadedFile($path, 'siswa.xlsx', null, null, true),
            ])->assertSessionHasErrors('file');

            $this->assertDatabaseMissing('students', ['nisn' => '0999999999']);
        } finally {
            @unlink($path);
        }
    }

    public function test_operator_can_delete_student_with_scores(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($operator)->delete(route('operator.students.destroy', $student))->assertRedirect();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('enrollments', ['student_id' => $student->id]);
    }

    public function test_operator_can_delete_unused_teacher_and_password_is_visible(): void
    {
        $operator = User::where('username', 'admin')->first();

        $this->actingAs($operator)->post(route('operator.teachers.store'), [
            'name' => 'Guru Baru',
            'username' => '3204999999999999',
            'password' => 'rahasia123',
            'gender' => 'L',
        ])->assertRedirect();

        $teacher = User::where('username', '3204999999999999')->firstOrFail();
        $this->assertSame('rahasia123', $teacher->password_plain);
        $this->actingAs($operator)->get(route('operator.teachers.index'))->assertSee('rahasia123');

        $this->actingAs($operator)->delete(route('operator.teachers.destroy', $teacher))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
    }

    public function test_teacher_with_teaching_assignment_cannot_be_deleted(): void
    {
        $operator = User::where('username', 'admin')->first();
        $teacher = User::factory()->create(['role' => Role::Guru]);
        $classroom = Classroom::factory()->create();
        TeachingAssignment::create([
            'period_id' => Period::factory()->create(['academic_year_id' => $classroom->academic_year_id])->id,
            'classroom_id' => $classroom->id,
            'subject_id' => Subject::first()->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($operator)->delete(route('operator.teachers.destroy', $teacher))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
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
}
