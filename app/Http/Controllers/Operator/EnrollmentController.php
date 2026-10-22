<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StudentMoveRequest;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;

class EnrollmentController extends Controller
{
    public function move(StudentMoveRequest $request, Student $student): RedirectResponse
    {
        $targetClassroom = Classroom::findOrFail($request->target_classroom_id);

        $enrollment = Enrollment::updateOrCreate(
            [
                'academic_year_id' => $targetClassroom->academic_year_id,
                'student_id' => $student->id,
            ],
            [
                'classroom_id' => $targetClassroom->id,
            ]
        );

        ActivityLogger::log('move_student_class', "Memindahkan siswa {$student->name} ke kelas {$targetClassroom->name}", $student);

        return redirect()->back()->with('success', "Siswa {$student->name} berhasil dipindahkan ke kelas {$targetClassroom->name}.");
    }
}
