<?php

namespace App\Http\Controllers\Operator;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\TeachingAssignmentRequest;
use App\Models\AppSetting;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Subject;
use App\Models\SubjectKktpDefault;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $activePeriod = (app()->bound('activePeriod') && app('activePeriod'))
            ? app('activePeriod')
            : Period::with('academicYear')->where('is_active', true)->first();
        $setting = AppSetting::first();
        $defaultKktp = $setting ? (float) $setting->default_kktp : 70.00;

        // Ambil daftar tingkat & kelas pada tahun aktif
        $academicYearId = $activePeriod?->academic_year_id;

        $classes = Classroom::when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $selectedClassId = $request->query('classroom_id', $classes->first()?->id);
        $selectedClass = $classes->firstWhere('id', $selectedClassId);

        // Ambil daftar guru aktif
        $teachers = User::where('role', Role::Guru)->where('is_active', true)->orderBy('name')->get();

        // Ambil struktur mapel (kurikulum sesuai kelas atau merdeka)
        $curriculum = $selectedClass ? $selectedClass->curriculum->value : 'merdeka';
        $subjects = Subject::with('parent')
            ->where('curriculum', $curriculum)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Ambil assignment yang sudah ada di kelas & periode ini
        $existingAssignments = [];
        $subjectKktpDefaults = [];
        if ($activePeriod && $selectedClass) {
            $existingAssignments = TeachingAssignment::where('period_id', $activePeriod->id)
                ->where('classroom_id', $selectedClass->id)
                ->get()
                ->keyBy('subject_id');

            $subjectKktpDefaults = SubjectKktpDefault::where('academic_year_id', $academicYearId)
                ->where('level', $selectedClass->level)
                ->get()
                ->keyBy('subject_id');
        }

        // Daftar periode lain untuk opsi sumber salin mengajar
        $otherPeriods = Period::with('academicYear')
            ->when($activePeriod, fn ($q) => $q->where('id', '!=', $activePeriod->id))
            ->latest('id')
            ->get();

        return view('operator.mengajar.index', compact(
            'activePeriod',
            'classes',
            'selectedClass',
            'teachers',
            'subjects',
            'existingAssignments',
            'subjectKktpDefaults',
            'defaultKktp',
            'otherPeriods'
        ));
    }

    public function storeOrUpdate(TeachingAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $assignment = TeachingAssignment::updateOrCreate(
            [
                'period_id' => $validated['period_id'],
                'classroom_id' => $validated['classroom_id'],
                'subject_id' => $validated['subject_id'],
            ],
            [
                'teacher_id' => $validated['teacher_id'],
                'hours_per_week' => $validated['hours_per_week'] ?? 2,
                'kktp' => $validated['kktp'] ?? 70.00,
                'weight_summative' => $validated['weight_summative'] ?? 50.00,
                'weight_sts' => $validated['weight_sts'] ?? 50.00,
            ]
        );

        ActivityLogger::log('save_teaching_assignment', "Mengatur guru mengajar mapel {$assignment->subject->name} di kelas {$assignment->classroom->name}", $assignment);

        return redirect()->back()->with('success', 'Data mengajar berhasil disimpan.');
    }

    public function destroy(TeachingAssignment $teachingAssignment): RedirectResponse
    {
        if ($teachingAssignment->learningScopes()->exists() || $teachingAssignment->stsScores()->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus data mengajar karena sudah ada nilai yang diinput oleh guru.');
        }

        $subjectName = $teachingAssignment->subject->name;
        $className = $teachingAssignment->classroom->name;
        $teachingAssignment->delete();

        ActivityLogger::log('delete_teaching_assignment', "Menghapus pengampu mapel {$subjectName} di kelas {$className}");

        return redirect()->back()->with('success', 'Pembagian mengajar berhasil dihapus.');
    }

    public function copy(Request $request, PeriodService $periodService): RedirectResponse
    {
        $activePeriod = app()->bound('activePeriod') ? app('activePeriod') : null;
        $request->validate([
            'source_period_id' => ['required', 'exists:periods,id', 'different:target_period_id'],
            'target_period_id' => ['required', Rule::in([$activePeriod?->id])],
        ]);

        $copiedCount = $periodService->copyTeachingAssignments(
            (int) $request->source_period_id,
            (int) $request->target_period_id
        );

        return redirect()->back()->with('success', "Berhasil menyalin {$copiedCount} pembagian mengajar ke periode aktif.");
    }
}
