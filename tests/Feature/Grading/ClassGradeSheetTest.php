<?php

namespace Tests\Feature\Grading;

use App\Enums\Achievement;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\CompetencyOverride;
use App\Models\Enrollment;
use App\Models\LearningScope;
use App\Models\Period;
use App\Models\StsScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SummativeScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Grading\ClassGradeSheet;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassGradeSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_class_grade_sheet_computes_scores_aggregates_and_completion_status(): void
    {
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
            'hours_per_week' => 2,
            'weight_summative' => 50.00,
            'weight_sts' => 50.00,
            'kktp' => 70.00,
        ]);

        // Buat 3 siswa
        $studentA = Student::factory()->create(['name' => 'Ahmad']);
        $studentB = Student::factory()->create(['name' => 'Budi']);
        $studentC = Student::factory()->create(['name' => 'Citra']);

        foreach ([$studentA, $studentB, $studentC] as $s) {
            Enrollment::create([
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'student_id' => $s->id,
            ]);
        }

        // Buat 2 LM
        $lm1 = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'materi dasar fiqih',
            'kktp' => 70.00,
        ]);
        $lm2 = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 2,
            'summary' => 'praktik bersuci',
            'kktp' => 70.00,
        ]);

        // Siswa A: Nilai lengkap (LM1=85, LM2=83, STS=80) -> Final 82 (Baik)
        SummativeScore::create(['learning_scope_id' => $lm1->id, 'student_id' => $studentA->id, 'score' => 85.00]);
        SummativeScore::create(['learning_scope_id' => $lm2->id, 'student_id' => $studentA->id, 'score' => 83.00]);
        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $studentA->id, 'score' => 80.00]);

        // Siswa B: Hanya ada sumatif, STS null
        SummativeScore::create(['learning_scope_id' => $lm1->id, 'student_id' => $studentB->id, 'score' => 80.00]);
        SummativeScore::create(['learning_scope_id' => $lm2->id, 'student_id' => $studentB->id, 'score' => 85.00]);

        // Siswa C: Hanya ada STS, sumatif kosong
        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $studentC->id, 'score' => 90.00]);

        $sheet = ClassGradeSheet::forAssignment($assignment);

        $this->assertSame(3, $sheet->totalStudents);
        $this->assertSame(1, $sheet->completeCount);
        $this->assertSame(2, $sheet->incompleteCount);
        $this->assertFalse($sheet->isAllComplete); // Tidak boleh kirim nilai karena belum lengkap
        $this->assertTrue($sheet->hasEmptySummativeCells);
        $this->assertSame(82.0, $sheet->classAverage);
        $this->assertSame('82,0', $sheet->formattedClassAverage);
        $this->assertSame(82, $sheet->highestScore);
        $this->assertSame(82, $sheet->lowestScore);

        $rowA = $sheet->rows->firstWhere('student.id', $studentA->id);
        $this->assertNotNull($rowA);
        $this->assertSame(82, $rowA->finalScore);
        $this->assertSame(Achievement::Baik, $rowA->achievement);
        $this->assertSame('Lengkap', $rowA->statusLabel);
        $this->assertTrue($rowA->isComplete);

        $rowB = $sheet->rows->firstWhere('student.id', $studentB->id);
        $this->assertNotNull($rowB);
        $this->assertNull($rowB->finalScore);
        $this->assertSame('Nilai STS belum diisi', $rowB->statusLabel);
        $this->assertFalse($rowB->isComplete);

        $rowC = $sheet->rows->firstWhere('student.id', $studentC->id);
        $this->assertNotNull($rowC);
        $this->assertNull($rowC->finalScore);
        $this->assertSame('Belum ada nilai sumatif', $rowC->statusLabel);
        $this->assertFalse($rowC->isComplete);
    }

    public function test_class_grade_sheet_becomes_all_complete_when_all_scores_filled(): void
    {
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
            'hours_per_week' => 2,
            'weight_summative' => 50.00,
            'weight_sts' => 50.00,
            'kktp' => 70.00,
        ]);

        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();

        foreach ([$studentA, $studentB] as $s) {
            Enrollment::create([
                'academic_year_id' => $year->id,
                'classroom_id' => $classroom->id,
                'student_id' => $s->id,
            ]);
        }

        $lm = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'materi aqidah',
            'kktp' => 70.00,
        ]);

        SummativeScore::create(['learning_scope_id' => $lm->id, 'student_id' => $studentA->id, 'score' => 90.00]);
        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $studentA->id, 'score' => 90.00]);

        SummativeScore::create(['learning_scope_id' => $lm->id, 'student_id' => $studentB->id, 'score' => 70.00]);
        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $studentB->id, 'score' => 70.00]);

        $sheet = ClassGradeSheet::forAssignment($assignment);

        $this->assertTrue($sheet->isAllComplete);
        $this->assertSame(2, $sheet->completeCount);
        $this->assertSame(0, $sheet->incompleteCount);
        $this->assertSame(90, $sheet->highestScore);
        $this->assertSame(70, $sheet->lowestScore);
        $this->assertSame('80,0', $sheet->formattedClassAverage);
        $this->assertSame(1, $sheet->achievementCounts[Achievement::SangatBaik->value]);
        $this->assertSame(1, $sheet->achievementCounts[Achievement::Cukup->value]);
    }

    public function test_competency_override_is_reflected_in_grade_sheet(): void
    {
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
            'weight_summative' => 50.00,
            'weight_sts' => 50.00,
            'kktp' => 70.00,
        ]);

        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $year->id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $lm = LearningScope::create([
            'teaching_assignment_id' => $assignment->id,
            'sequence' => 1,
            'summary' => 'materi bahasa arab',
            'kktp' => 70.00,
        ]);

        SummativeScore::create(['learning_scope_id' => $lm->id, 'student_id' => $student->id, 'score' => 85.00]);
        StsScore::create(['teaching_assignment_id' => $assignment->id, 'student_id' => $student->id, 'score' => 85.00]);

        // Simpan override deskripsi capaian
        $customText = 'Menunjukkan penguasaan istimewa dalam percakapan sehari-hari.';
        CompetencyOverride::create([
            'teaching_assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'description' => $customText,
            'updated_by' => $teacher->id,
        ]);

        $sheet = ClassGradeSheet::forAssignment($assignment);
        $row = $sheet->rows->first();

        $this->assertTrue($row->isDescriptionOverridden);
        $this->assertSame($customText, $row->description);
    }
}
