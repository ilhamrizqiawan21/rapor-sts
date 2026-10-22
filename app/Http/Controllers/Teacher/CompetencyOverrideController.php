<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\CompetencyOverrideRequest;
use App\Models\CompetencyOverride;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;

class CompetencyOverrideController extends Controller
{
    public function update(CompetencyOverrideRequest $request, TeachingAssignment $teachingAssignment, Student $student): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        $validated = $request->validated();

        CompetencyOverride::updateOrCreate(
            [
                'teaching_assignment_id' => $teachingAssignment->id,
                'student_id' => $student->id,
            ],
            [
                'description' => trim($validated['description']),
                'updated_by' => $request->user()->id,
            ]
        );

        ActivityLogger::log(
            'override_competency',
            "Mengubah manual deskripsi capaian siswa {$student->name} pada mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name}",
            $teachingAssignment
        );

        return redirect()->back()->with('success', "Deskripsi capaian siswa {$student->name} berhasil disimpan.");
    }

    public function destroy(TeachingAssignment $teachingAssignment, Student $student): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        CompetencyOverride::where('teaching_assignment_id', $teachingAssignment->id)
            ->where('student_id', $student->id)
            ->delete();

        ActivityLogger::log(
            'reset_competency_override',
            "Mengembalikan deskripsi capaian siswa {$student->name} ke otomatis pada mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name}",
            $teachingAssignment
        );

        return redirect()->back()->with('success', "Deskripsi capaian siswa {$student->name} berhasil dikembalikan ke otomatis.");
    }
}
