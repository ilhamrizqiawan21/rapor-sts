<?php

namespace Tests\Feature\Homeroom;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomLock;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivity;
use App\Models\ExtracurricularRecord;
use App\Models\HomeroomRecord;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use App\Services\Import\TemplateBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HomeroomRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_non_homeroom_teacher_cannot_access_homeroom_records(): void
    {
        $teacher = User::factory()->guru()->create();

        $response = $this->actingAs($teacher)->get(route('wali.kehadiran.index'));
        $response->assertStatus(403);
    }

    public function test_homeroom_teacher_can_view_records_page(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create(['name' => 'Ahmad Siswa']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($teacher)->get(route('wali.kehadiran.index'));
        $response->assertStatus(200);
        $response->assertSee('Kehadiran &amp; Catatan Wali Kelas', false);
        $response->assertSee('Ahmad Siswa');
    }

    public function test_homeroom_teacher_can_update_attendance_and_notes(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.kehadiran.update'), [
            'records' => [
                [
                    'student_id' => $student->id,
                    'sick' => 2,
                    'permit' => 1,
                    'absent' => 0,
                    'note' => 'Pertahankan prestasimu.',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('homeroom_records', [
            'period_id' => $period->id,
            'student_id' => $student->id,
            'sick' => 2,
            'permit' => 1,
            'absent' => 0,
            'note' => 'Pertahankan prestasimu.',
        ]);
    }

    public function test_assigned_extracurricular_teacher_can_save_assessment(): void
    {
        $homeroomTeacher = User::factory()->guru()->create();
        $supervisor = User::factory()->guru()->create(['name' => 'Guru Pembina']);
        $year = AcademicYear::factory()->create(['name' => '2034/2035']);
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);
        $student = Student::factory()->create(['name' => 'Siswa Ekstrakurikuler']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
        $activity = ExtracurricularActivity::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'name' => 'Pramuka',
            'supervisor_id' => $supervisor->id,
        ]);
        $record = ExtracurricularRecord::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'supervisor_id' => $supervisor->id,
            'extracurricular_activity_id' => $activity->id,
            'name' => 'Pramuka',
            'predicate' => 'Baik',
        ]);

        $response = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)->post(route('guru.ekstrakurikuler.update'), [
                'classroom_id' => $classroom->id,
                'records' => [
                    $record->id => [
                        'predicate' => 'Sangat Baik',
                        'description' => 'Aktif mengikuti latihan.',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'name' => 'Pramuka',
            'predicate' => 'Sangat Baik',
            'supervisor_id' => $supervisor->id,
        ]);

        $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)->get(route('guru.ekstrakurikuler.index'))
            ->assertOk()
            ->assertSee('Pramuka')
            ->assertSee('Anda adalah guru pembina');

        $this->withSession(['period_id' => $period->id])
            ->actingAs($homeroomTeacher)->get(route('guru.ekstrakurikuler.index'))
            ->assertStatus(403);
    }

    public function test_operator_can_view_and_save_extracurricular_records_for_selected_classroom(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $supervisor = User::factory()->guru()->create(['name' => 'Pembina Operator']);
        $year = AcademicYear::factory()->create(['name' => '2044/2045']);
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $student = Student::factory()->create(['name' => 'Siswa Operator Ekstra']);
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->get(route('operator.extracurricular.index', ['classroom_id' => $classroom->id]))
            ->assertOk()
            ->assertSee('Siswa Operator Ekstra')
            ->assertSee('Pembina Operator');

        $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->post(route('operator.extracurricular.update'), [
                'classroom_id' => $classroom->id,
                'extracurriculars' => [
                    $student->id => [[
                        'name' => 'Pramuka',
                        'supervisor_id' => $supervisor->id,
                        'predicate' => 'Sangat Baik',
                        'description' => 'Aktif.',
                    ]],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'name' => 'Pramuka',
            'supervisor_id' => $supervisor->id,
        ]);
    }

    public function test_one_extracurricular_activity_uses_one_supervisor_for_multiple_students(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $supervisor = User::factory()->guru()->create(['name' => 'Pembina Pramuka']);
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $students = Student::factory()->count(2)->create();

        foreach ($students as $student) {
            Enrollment::create([
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'student_id' => $student->id,
            ]);
        }

        $response = $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->post(route('operator.extracurricular.update'), [
                'classroom_id' => $classroom->id,
                'activities' => [
                    'pramuka' => [
                        'name' => 'Pramuka',
                        'supervisor_id' => $supervisor->id,
                    ],
                ],
                'student_records' => $students->mapWithKeys(fn ($student) => [
                    $student->id => [
                        'record' => [
                            'activity_key' => 'pramuka',
                            'predicate' => 'Baik',
                            'description' => null,
                        ],
                    ],
                ])->all(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('extracurricular_activities', 1);
        $this->assertDatabaseCount('extracurricular_records', 2);
        $this->assertDatabaseHas('extracurricular_activities', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'name' => 'Pramuka',
            'supervisor_id' => $supervisor->id,
        ]);
        $this->assertSame(
            [$supervisor->id],
            ExtracurricularRecord::query()->pluck('supervisor_id')->unique()->all()
        );
    }

    public function test_teacher_without_activity_supervisor_assignment_cannot_update_activity_assessment(): void
    {
        $assignedTeacher = User::factory()->guru()->create();
        $otherTeacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
        $activity = ExtracurricularActivity::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'name' => 'Paskibra',
            'supervisor_id' => $assignedTeacher->id,
        ]);
        $record = ExtracurricularRecord::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'supervisor_id' => $assignedTeacher->id,
            'extracurricular_activity_id' => $activity->id,
            'name' => 'Paskibra',
            'predicate' => 'Baik',
        ]);

        $response = $this->withSession(['period_id' => $period->id])
            ->actingAs($otherTeacher)
            ->post(route('guru.ekstrakurikuler.update'), [
                'classroom_id' => $classroom->id,
                'records' => [
                    $record->id => [
                        'predicate' => 'Sangat Baik',
                        'description' => 'Tidak berwenang.',
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('classroom_id');
        $this->assertDatabaseHas('extracurricular_records', [
            'id' => $record->id,
            'predicate' => 'Baik',
            'description' => null,
        ]);
    }

    public function test_homeroom_teacher_can_fill_batch_notes_without_overwriting_existing(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $student1 = Student::factory()->create(['name' => 'Siswa Satu']);
        $student2 = Student::factory()->create(['name' => 'Siswa Dua']);

        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student1->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student2->id]);

        // Siswa 1 sudah punya catatan khusus
        HomeroomRecord::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student1->id,
            'sick' => 0,
            'permit' => 0,
            'absent' => 0,
            'note' => 'Catatan khusus siswa 1',
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.kehadiran.batch_note'), [
            'note' => 'Catatan massal default',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Siswa 1 TIDAK tertimpa
        $this->assertDatabaseHas('homeroom_records', [
            'student_id' => $student1->id,
            'note' => 'Catatan khusus siswa 1',
        ]);

        // Siswa 2 terisi catatan massal
        $this->assertDatabaseHas('homeroom_records', [
            'student_id' => $student2->id,
            'note' => 'Catatan massal default',
        ]);
    }

    public function test_homeroom_teacher_can_download_template(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $response = $this->actingAs($teacher)->get(route('wali.kehadiran.template'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    public function test_homeroom_teacher_can_import_excel(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
            'name' => 'VII.A',
        ]);

        $student = Student::factory()->create();
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        $builder = new TemplateBuilder;
        $spreadsheet = $builder->buildHomeroomTemplate($classroom, $period);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('D4', 3); // Sakit
        $sheet->setCellValue('E4', 2); // Izin
        $sheet->setCellValue('F4', 1); // Alpa
        $sheet->setCellValue('G4', 'Hasil import');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_homeroom_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $sheet->setCellValue('D4', 1.5);
        $writer->save($tempFile);

        $this->actingAs($teacher)->post(route('wali.kehadiran.import'), [
            'file' => new UploadedFile($tempFile, 'import.xlsx', null, null, true),
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('homeroom_records', ['student_id' => $student->id]);

        $sheet->setCellValue('D4', 3);
        $writer->save($tempFile);

        $uploadedFile = new UploadedFile($tempFile, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($teacher)->post(route('wali.kehadiran.import'), [
            'file' => $uploadedFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('homeroom_records', [
            'student_id' => $student->id,
            'sick' => 3,
            'permit' => 2,
            'absent' => 1,
            'note' => 'Hasil import',
        ]);

        @unlink($tempFile);
    }

    public function test_locked_classroom_prevents_updates(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create([
            'academic_year_id' => $year->id,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        ClassroomLock::create([
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'locked_by' => $teacher->id,
            'locked_at' => now(),
        ]);

        $response = $this->actingAs($teacher)->post(route('wali.kehadiran.update'), [
            'records' => [
                [
                    'student_id' => $student->id,
                    'sick' => 1,
                ],
            ],
        ]);

        $response->assertStatus(403);
    }
}
