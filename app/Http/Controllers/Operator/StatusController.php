<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Homeroom\StatusController as HomeroomStatusController;
use App\Models\Classroom;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatusController extends Controller
{
    public function __construct(
        protected HomeroomStatusController $homeroomStatusController
    ) {}

    public function index(Request $request): View
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $classrooms = Classroom::with('homeroomTeacher')
            ->where('academic_year_id', $period->academic_year_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $selectedClassroomId = $request->query('classroom_id', $classrooms->first()?->id);
        $selectedClassroom = $classrooms->firstWhere('id', (int) $selectedClassroomId) ?? $classrooms->first();

        $statusData = [];
        if ($selectedClassroom) {
            $statusData = $this->homeroomStatusController->computeClassStatus($selectedClassroom, $period);
        }

        return view('operator.rapor.status', array_merge($statusData, [
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'period' => $period,
            'isHomeroomView' => false,
        ]));
    }
}
