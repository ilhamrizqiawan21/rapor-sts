<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StsScoreRequest;
use App\Models\StsScore;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StsScoreController extends Controller
{
    public function index(TeachingAssignment $teachingAssignment): View
    {
        $this->authorize('view', $teachingAssignment);

        $teachingAssignment->loadMissing([
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $teachingAssignment->period->academic_year_id)->with('student'),
            'stsScores',
            'subject',
        ]);

        $students = $teachingAssignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $scores = $teachingAssignment->stsScores->keyBy('student_id');

        return view('guru.sts', compact('teachingAssignment', 'students', 'scores'));
    }

    public function update(StsScoreRequest $request, TeachingAssignment $teachingAssignment): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $teachingAssignment) {
            foreach ($validated['scores'] as $studentId => $score) {
                $scoreVal = ($score !== null && $score !== '') ? (float) $score : null;

                StsScore::updateOrCreate(
                    [
                        'teaching_assignment_id' => $teachingAssignment->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'period_id' => $teachingAssignment->period_id,
                        'score' => $scoreVal,
                    ]
                );
            }
        });

        ActivityLogger::log(
            'update_sts_scores',
            "Menyimpan nilai tes STS untuk mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name}",
            $teachingAssignment
        );

        return redirect()->back()->with('success', 'Nilai STS berhasil disimpan.');
    }
}
