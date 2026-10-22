<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\KktpDefaultRequest;
use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Period;
use App\Models\Subject;
use App\Models\SubjectKktpDefault;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KktpDefaultController extends Controller
{
    public function index(Request $request): View
    {
        $activePeriod = (app()->bound('activePeriod') && app('activePeriod'))
            ? app('activePeriod')
            : Period::with('academicYear')->where('is_active', true)->first();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();

        $selectedYearId = $request->query('academic_year_id', $activePeriod?->academic_year_id);
        $selectedLevel = (int) $request->query('level', 7);

        $setting = AppSetting::first();
        $globalKktp = $setting ? (float) $setting->default_kktp : 70.00;

        $subjects = Subject::whereDoesntHave('children')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $kktpDefaults = [];
        if ($selectedYearId) {
            $kktpDefaults = SubjectKktpDefault::where('academic_year_id', $selectedYearId)
                ->where('level', $selectedLevel)
                ->get()
                ->keyBy('subject_id');
        }

        return view('operator.kktp.index', compact(
            'academicYears',
            'selectedYearId',
            'selectedLevel',
            'subjects',
            'kktpDefaults',
            'globalKktp'
        ));
    }

    public function storeOrUpdate(KktpDefaultRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $kktp = SubjectKktpDefault::updateOrCreate(
            [
                'academic_year_id' => $validated['academic_year_id'],
                'subject_id' => $validated['subject_id'],
                'level' => $validated['level'],
            ],
            [
                'kktp' => $validated['kktp'],
            ]
        );

        ActivityLogger::log('update_kktp_default', "Mengatur KKTP default mapel {$kktp->subject->name} tingkat {$kktp->level} menjadi {$kktp->kktp}", $kktp);

        return redirect()->back()->with('success', 'KKTP default mapel berhasil diperbarui.');
    }
}
