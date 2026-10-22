<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeachingAssignment;
use App\Services\Grading\ClassGradeSheet;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(TeachingAssignment $teachingAssignment): View
    {
        $this->authorize('view', $teachingAssignment);

        $sheet = ClassGradeSheet::forAssignment($teachingAssignment);

        return view('guru.penilaian', compact('teachingAssignment', 'sheet'));
    }
}
