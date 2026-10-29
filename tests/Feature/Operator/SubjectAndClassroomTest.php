<?php

namespace Tests\Feature\Operator;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\MadrasahProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectAndClassroomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_operator_can_create_subject(): void
    {
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->post(route('operator.subjects.store'), [
            'code' => 'KDG',
            'name' => 'Kaidah Dasar Guru',
            'curriculum' => 'merdeka',
            'group' => 'mulok',
            'sort_order' => 20,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['code' => 'KDG', 'name' => 'Kaidah Dasar Guru']);
    }

    public function test_duplicate_subject_code_in_same_curriculum_is_rejected(): void
    {
        $operator = User::where('username', 'admin')->first();

        $response = $this->actingAs($operator)->post(route('operator.subjects.store'), [
            'code' => 'MTK', // MTK sudah ada di Merdeka dari seeder
            'name' => 'Matematika Peminatan',
            'curriculum' => 'merdeka',
            'group' => 'umum',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_operator_can_move_subject_order_up_and_down(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $first = Subject::create([
            'code' => 'ORD1',
            'name' => 'Mapel Urutan Satu',
            'group' => 'umum',
            'curriculum' => 'merdeka',
            'sort_order' => 90,
            'is_active' => true,
        ]);
        $second = Subject::create([
            'code' => 'ORD2',
            'name' => 'Mapel Urutan Dua',
            'group' => 'umum',
            'curriculum' => 'merdeka',
            'sort_order' => 91,
            'is_active' => true,
        ]);

        $this->actingAs($operator)
            ->patch(route('operator.subjects.move', $first), ['direction' => 'down'])
            ->assertRedirect(route('operator.subjects.index', ['curriculum' => 'merdeka']));

        $this->assertGreaterThan($second->fresh()->sort_order, $first->fresh()->sort_order);

        $this->actingAs($operator)
            ->patch(route('operator.subjects.move', $first), ['direction' => 'up'])
            ->assertRedirect(route('operator.subjects.index', ['curriculum' => 'merdeka']));

        $this->assertLessThan($second->fresh()->sort_order, $first->fresh()->sort_order);
    }

    public function test_operator_can_create_batch_classrooms(): void
    {
        $operator = User::where('username', 'admin')->first();
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($operator)->post(route('operator.classrooms.batch'), [
            'academic_year_id' => $year->id,
            'level' => 7,
            'prefix' => 'VII.',
            'parallels' => 'A, B, C',
            'curriculum' => 'merdeka',
            'phase' => 'D',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', ['academic_year_id' => $year->id, 'name' => 'VII.A']);
        $this->assertDatabaseHas('classrooms', ['academic_year_id' => $year->id, 'name' => 'VII.B']);
        $this->assertDatabaseHas('classrooms', ['academic_year_id' => $year->id, 'name' => 'VII.C']);
    }

    public function test_classroom_levels_follow_madrasah_jenjang_from_mi_to_ma(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $profile = MadrasahProfile::firstOrFail();
        $year = AcademicYear::factory()->create();

        foreach ([
            ['MI', 1, 'I.A', 6, 'VI.A'],
            ['MTs', 7, 'VII.A', 9, 'IX.A'],
            ['MA', 10, 'X.A', 12, 'XII.A'],
        ] as [$jenjang, $firstLevel, $firstName, $lastLevel, $lastName]) {
            $profile->update(['level' => $jenjang]);

            $this->actingAs($operator)
                ->get(route('operator.classrooms.index', ['academic_year_id' => $year->id]))
                ->assertOk()
                ->assertSee(MadrasahProfile::classLevelLabel($firstLevel))
                ->assertSee(MadrasahProfile::classLevelLabel($lastLevel));

            $this->actingAs($operator)->post(route('operator.classrooms.store'), [
                'academic_year_id' => $year->id,
                'level' => MadrasahProfile::classLevelRoman($firstLevel),
                'name' => $firstName,
                'curriculum' => 'merdeka',
            ])->assertRedirect()->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('classrooms', [
                'academic_year_id' => $year->id,
                'name' => $firstName,
                'phase' => MadrasahProfile::phaseForClassLevel($firstLevel),
            ]);

            $this->actingAs($operator)->post(route('operator.classrooms.store'), [
                'academic_year_id' => $year->id,
                'level' => $firstLevel - 1,
                'name' => $lastName,
                'curriculum' => 'merdeka',
            ])->assertSessionHasErrors('level');
        }
    }

    public function test_cannot_delete_classroom_with_enrolled_students(): void
    {
        $operator = User::where('username', 'admin')->first();
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create();
        Enrollment::create([
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.classrooms.destroy', $classroom));

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id]);
    }

    public function test_only_active_teacher_can_be_assigned_as_homeroom_teacher(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $year = AcademicYear::factory()->create();
        $headmaster = User::factory()->kepala()->create();

        $this->actingAs($operator)->post(route('operator.classrooms.store'), [
            'academic_year_id' => $year->id,
            'level' => 7,
            'name' => 'VII.A',
            'curriculum' => 'merdeka',
            'homeroom_teacher_id' => $headmaster->id,
        ])->assertSessionHasErrors('homeroom_teacher_id');

        $this->assertDatabaseMissing('classrooms', ['academic_year_id' => $year->id, 'name' => 'VII.A']);
    }

    public function test_operator_can_assign_change_and_clear_homeroom_teacher_for_existing_classroom(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => null]);
        $firstTeacher = User::factory()->guru()->create();
        $secondTeacher = User::factory()->guru()->create();

        $this->actingAs($operator)->get(route('operator.classrooms.index', [
            'academic_year_id' => $classroom->academic_year_id,
        ]))->assertOk()
            ->assertSee('name="homeroom_teacher_id"', false)
            ->assertSee('onchange="this.form.requestSubmit()"', false)
            ->assertSee($firstTeacher->name)
            ->assertDontSee('Atur Wali Kelas');

        foreach ([$firstTeacher->id, $secondTeacher->id, null] as $teacherId) {
            $this->actingAs($operator)->patch(route('operator.classrooms.homeroom.update', $classroom), [
                'homeroom_teacher_id' => $teacherId,
            ])->assertRedirect(route('operator.classrooms.index', [
                'academic_year_id' => $classroom->academic_year_id,
            ]));

            $this->assertDatabaseHas('classrooms', [
                'id' => $classroom->id,
                'homeroom_teacher_id' => $teacherId,
            ]);
        }
    }

    public function test_homeroom_assignment_rejects_non_teacher_and_inactive_teacher(): void
    {
        $operator = User::where('username', 'admin')->firstOrFail();
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => null]);
        $headmaster = User::factory()->kepala()->create();
        $inactiveTeacher = User::factory()->guru()->inactive()->create();

        foreach ([$headmaster->id, $inactiveTeacher->id] as $teacherId) {
            $this->actingAs($operator)->patch(route('operator.classrooms.homeroom.update', $classroom), [
                'homeroom_teacher_id' => $teacherId,
            ])->assertSessionHasErrors('homeroom_teacher_id');
        }

        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id, 'homeroom_teacher_id' => null]);
    }

    public function test_teacher_cannot_change_homeroom_assignment(): void
    {
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => null]);
        $teacher = User::factory()->guru()->create();

        $this->actingAs($teacher)->patch(route('operator.classrooms.homeroom.update', $classroom), [
            'homeroom_teacher_id' => $teacher->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id, 'homeroom_teacher_id' => null]);
    }
}
