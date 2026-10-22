<?php

namespace App\Http\Controllers\Operator;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $teacherCount = User::where('role', Role::Guru)->where('is_active', true)->count();
        $studentCount = Student::where('is_active', true)->count();

        $activePeriod = (app()->bound('activePeriod') && app('activePeriod'))
            ? app('activePeriod')
            : Period::with('academicYear')->where('is_active', true)->first();

        $classCount = 0;
        $submittedPercent = 0;

        if ($activePeriod) {
            $classCount = Classroom::where('academic_year_id', $activePeriod->academic_year_id)->count();

            $totalAssignments = TeachingAssignment::where('period_id', $activePeriod->id)->count();
            $submittedAssignments = TeachingAssignment::where('period_id', $activePeriod->id)
                ->whereNotNull('submitted_at')
                ->count();

            $submittedPercent = $totalAssignments > 0
                ? (int) round(($submittedAssignments / $totalAssignments) * 100)
                : 0;
        }

        $activities = ActivityLog::with('user')
            ->latest('created_at')
            ->paginate(10);

        return view('operator.dashboard', compact(
            'teacherCount',
            'studentCount',
            'classCount',
            'submittedPercent',
            'activities',
            'activePeriod'
        ));
    }
}
