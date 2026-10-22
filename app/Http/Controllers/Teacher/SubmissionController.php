<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use App\Services\Grading\ClassGradeSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function submit(Request $request, TeachingAssignment $teachingAssignment): RedirectResponse
    {
        $this->authorize('submit', $teachingAssignment);

        $sheet = ClassGradeSheet::forAssignment($teachingAssignment);

        if (! $sheet->isAllComplete) {
            return redirect()->back()->with(
                'error',
                "Tidak dapat mengirim nilai: Masih terdapat {$sheet->incompleteCount} siswa aktif yang belum memiliki nilai akhir rapor STS lengkap."
            );
        }

        $teachingAssignment->update([
            'submitted_at' => now(),
            'submitted_by' => $request->user()->id,
        ]);

        ActivityLogger::log(
            'submit_scores',
            "Mengirim nilai rapor STS mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name}",
            $teachingAssignment
        );

        return redirect()->back()->with(
            'success',
            "Nilai mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name} berhasil dikirim ke wali kelas dan telah dikunci untuk guru."
        );
    }
}
