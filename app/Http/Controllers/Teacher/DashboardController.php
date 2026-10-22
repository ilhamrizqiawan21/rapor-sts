<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activePeriod = (app()->bound('activePeriod') && app('activePeriod'))
            ? app('activePeriod')
            : Period::with('academicYear')->where('is_active', true)->first();

        $assignments = TeachingAssignment::with(['subject', 'classroom'])
            ->where('period_id', $activePeriod?->id)
            ->where('teacher_id', $user->id)
            ->get();

        $totalClasses = $assignments->count();
        $submittedClasses = $assignments->filter(fn ($a) => $a->isSubmitted())->count();
        $draftClasses = $totalClasses - $submittedClasses;

        $homeroomClass = Classroom::where('academic_year_id', $activePeriod?->academic_year_id)
            ->where('homeroom_teacher_id', $user->id)
            ->first();

        return view('guru.dashboard', compact(
            'activePeriod',
            'assignments',
            'totalClasses',
            'submittedClasses',
            'draftClasses',
            'homeroomClass'
        ));
    }
}
