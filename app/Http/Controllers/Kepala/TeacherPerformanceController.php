<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Period;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\View\View;

class TeacherPerformanceController extends Controller
{
    public function index(): View
    {
        /** @var Period $period */
        $period = app('activePeriod');

        // Ambil semua penugasan mengajar di periode aktif
        $assignments = TeachingAssignment::where('period_id', $period->id)
            ->with(['teacher', 'subject', 'classroom'])
            ->get();

        $teacherIds = $assignments->pluck('teacher_id')->unique()->filter();

        $teachers = User::whereIn('id', $teacherIds)
            ->orderBy('name')
            ->get();

        // Ambil aktivitas terakhir per guru dalam 1 query
        $lastActivities = ActivityLog::whereIn('user_id', $teacherIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($logs) => $logs->first());

        $teacherRows = [];

        foreach ($teachers as $teacher) {
            $tAssignments = $assignments->where('teacher_id', $teacher->id);
            $total = $tAssignments->count();
            $submitted = $tAssignments->filter(fn ($a) => $a->isSubmitted())->count();
            $percent = $total > 0 ? (int) round(($submitted / $total) * 100) : 0;

            $subjectsList = $tAssignments->pluck('subject.name')->unique()->implode(', ');
            $classesList = $tAssignments->pluck('classroom.name')->unique()->implode(', ');

            $lastLog = $lastActivities->get($teacher->id);
            $lastActive = $lastLog ? $lastLog->created_at->translatedFormat('d M Y, H:i') : '–';

            $teacherRows[] = [
                'teacher' => $teacher,
                'subjects' => $subjectsList,
                'classes' => $classesList,
                'total' => $total,
                'submitted' => $submitted,
                'percent' => $percent,
                'last_active' => $lastActive,
            ];
        }

        return view('kepala.teacher-performance', [
            'teacherRows' => $teacherRows,
            'period' => $period,
        ]);
    }
}
