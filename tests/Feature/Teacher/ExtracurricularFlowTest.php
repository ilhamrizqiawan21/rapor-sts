<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivityDefinition;
use App\Models\Period;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtracurricularFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_creates_activity_and_assigned_teacher_sees_the_menu_without_participants(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $supervisor = User::factory()->guru()->create(['name' => 'Pembina Pramuka']);
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        Classroom::factory()->create(['academic_year_id' => $year->id]);

        $response = $this->actingAs($operator)
            ->withSession(['period_id' => $period->id])
            ->post(route('operator.extracurricular.activities.update'), [
                'activities' => [
                    'pramuka' => [
                        'name' => 'Pramuka',
                        'supervisor_id' => $supervisor->id,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $activity = ExtracurricularActivityDefinition::firstOrFail();
        $this->assertSame('Pramuka', $activity->name);
        $this->assertSame($supervisor->id, $activity->supervisor_id);

        $this->withSession(['period_id' => $period->id])
            ->actingAs($operator)
            ->get(route('operator.extracurricular.activities.index'))
            ->assertOk()
            ->assertSee('Kegiatan Ekstrakurikuler')
            ->assertSee('Pembina Pramuka');

        $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->get(route('guru.ekstrakurikuler.index'))
            ->assertOk()
            ->assertSee('Pramuka')
            ->assertSee('Belum ada peserta');
    }

    public function test_supervisor_selects_participants_per_class_and_saves_a_to_d_grade(): void
    {
        $supervisor = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id]);
        $studentOne = Student::factory()->create(['name' => 'Peserta Satu']);
        $studentTwo = Student::factory()->create(['name' => 'Bukan Peserta']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentOne->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentTwo->id]);
        $activity = ExtracurricularActivityDefinition::create([
            'period_id' => $period->id,
            'name' => 'Paskibra',
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->post(route('guru.ekstrakurikuler.update'), [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'participant_ids' => [$studentOne->id],
                'records' => [
                    $studentOne->id => [
                        'grade' => 'A',
                        'description' => 'Sangat aktif.',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $studentOne->id,
            'activity_definition_id' => $activity->id,
            'predicate' => 'A',
            'description' => 'Sangat aktif.',
        ]);
        $this->assertDatabaseMissing('extracurricular_records', [
            'classroom_id' => $classroom->id,
            'student_id' => $studentTwo->id,
            'activity_definition_id' => $activity->id,
        ]);

        $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->get(route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
            ]))
            ->assertOk()
            ->assertSee('Peserta Satu')
            ->assertSee('A — Sangat Baik');
    }

    public function test_teacher_without_activity_assignment_cannot_open_extracurricular_menu(): void
    {
        $teacher = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        Classroom::factory()->create(['academic_year_id' => $year->id]);

        $this->withSession(['period_id' => $period->id])
            ->actingAs($teacher)
            ->get(route('guru.ekstrakurikuler.index'))
            ->assertForbidden();
    }

    public function test_mandatory_activity_automatically_includes_all_students_for_scoring(): void
    {
        $supervisor = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A', 'level' => 7]);
        $studentOne = Student::factory()->create(['name' => 'Siswa Pertama', 'gender' => 'L']);
        $studentTwo = Student::factory()->create(['name' => 'Siswa Kedua', 'gender' => 'P']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentOne->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentTwo->id]);

        $activity = ExtracurricularActivityDefinition::create([
            'period_id' => $period->id,
            'name' => 'Pramuka',
            'supervisor_id' => $supervisor->id,
            'is_mandatory' => true,
        ]);

        // When viewing the index, both students should be present without needing to add them
        $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->get(route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
            ]))
            ->assertOk()
            ->assertSee('Pramuka')
            ->assertSee('Wajib')
            ->assertSee('Siswa Pertama')
            ->assertSee('Siswa Kedua');

        // Submit scores for both mandatory students
        $response = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->post(route('guru.ekstrakurikuler.update'), [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'participant_ids' => [$studentOne->id, $studentTwo->id],
                'records' => [
                    $studentOne->id => ['grade' => 'A'],
                    $studentTwo->id => ['grade' => 'B'],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $studentOne->id,
            'activity_definition_id' => $activity->id,
            'predicate' => 'A',
        ]);
        $this->assertDatabaseHas('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $studentTwo->id,
            'activity_definition_id' => $activity->id,
            'predicate' => 'B',
        ]);
    }

    public function test_non_mandatory_activity_allows_managing_members_then_scoring(): void
    {
        $supervisor = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A', 'level' => 7]);
        $studentOne = Student::factory()->create(['name' => 'Peserta Futsal']);
        $studentTwo = Student::factory()->create(['name' => 'Bukan Peserta Futsal']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentOne->id]);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $studentTwo->id]);

        $activity = ExtracurricularActivityDefinition::create([
            'period_id' => $period->id,
            'name' => 'Futsal',
            'supervisor_id' => $supervisor->id,
            'is_mandatory' => false,
        ]);

        // Step 1: Manage members (only studentOne joins)
        $memberResponse = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->post(route('guru.ekstrakurikuler.members.update'), [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'member_ids' => [$studentOne->id],
            ]);

        $memberResponse->assertRedirect();
        $this->assertDatabaseHas('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $studentOne->id,
            'activity_definition_id' => $activity->id,
        ]);
        $this->assertDatabaseMissing('extracurricular_records', [
            'period_id' => $period->id,
            'classroom_id' => $classroom->id,
            'student_id' => $studentTwo->id,
            'activity_definition_id' => $activity->id,
        ]);

        // Step 2: Index displays only studentOne in participants
        $view = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->get(route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
            ]));

        $view->assertOk();
        $this->assertCount(1, $view->viewData('participants'));
        $this->assertSame($studentOne->id, $view->viewData('participants')->first()->id);

        // Step 3: Input grade
        $gradeResponse = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->post(route('guru.ekstrakurikuler.update'), [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'participant_ids' => [$studentOne->id],
                'records' => [
                    $studentOne->id => ['grade' => 'A', 'description' => 'Kapten tim'],
                ],
            ]);

        $gradeResponse->assertRedirect();
        $this->assertDatabaseHas('extracurricular_records', [
            'student_id' => $studentOne->id,
            'predicate' => 'A',
            'description' => 'Kapten tim',
        ]);
    }

    public function test_supervisor_can_export_extracurricular_excel_template(): void
    {
        $supervisor = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A', 'level' => 7]);
        $student = Student::factory()->create(['name' => 'Siswa Template']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        $activity = ExtracurricularActivityDefinition::create([
            'period_id' => $period->id,
            'name' => 'Pramuka',
            'supervisor_id' => $supervisor->id,
            'is_mandatory' => true,
        ]);

        $response = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->get(route('guru.ekstrakurikuler.export', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_supervisor_can_import_extracurricular_excel_scores(): void
    {
        $supervisor = User::factory()->guru()->create();
        $year = AcademicYear::factory()->create();
        $period = Period::factory()->create(['academic_year_id' => $year->id, 'is_active' => true]);
        $classroom = Classroom::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII.A', 'level' => 7]);
        $student = Student::factory()->create(['name' => 'Siswa Import', 'nisn' => '1234567890']);
        Enrollment::create(['academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'student_id' => $student->id]);

        $activity = ExtracurricularActivityDefinition::create([
            'period_id' => $period->id,
            'name' => 'Pramuka',
            'supervisor_id' => $supervisor->id,
            'is_mandatory' => true,
        ]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'NISN');
        $sheet->setCellValue('C6', 'Nama Siswa');
        $sheet->setCellValue('D6', 'L/P');
        $sheet->setCellValue('E6', 'Nilai');
        $sheet->setCellValue('F6', 'Keterangan');

        $sheet->setCellValue('A7', '1');
        $sheet->setCellValue('B7', '1234567890');
        $sheet->setCellValue('C7', 'Siswa Import');
        $sheet->setCellValue('D7', 'L');
        $sheet->setCellValue('E7', 'A');
        $sheet->setCellValue('F7', 'Sangat memuaskan');

        $tempPath = tempnam(sys_get_temp_dir(), 'excel_test');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempPath,
            'rdm_ekskul.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->withSession(['period_id' => $period->id])
            ->actingAs($supervisor)
            ->post(route('guru.ekstrakurikuler.import'), [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'template_file' => $uploadedFile,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('extracurricular_records', [
            'student_id' => $student->id,
            'activity_definition_id' => $activity->id,
            'predicate' => 'A',
            'description' => 'Sangat memuaskan',
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}
