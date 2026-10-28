<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ExtracurricularRequest;
use App\Models\Classroom;
use App\Models\ExtracurricularRecord;
use App\Models\Period;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExtracurricularController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var Period|null $period */
        $period = app()->bound('activePeriod') ? app('activePeriod') : null;

        abort_unless($period, 404, 'Periode aktif tidak ditemukan.');

        $assignedClassroomIds = ExtracurricularRecord::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $user->id)
            ->distinct()
            ->pluck('classroom_id');

        abort_if($assignedClassroomIds->isEmpty(), 403, 'Anda belum ditugaskan sebagai guru pembina.');

        $classrooms = Classroom::query()
            ->with('academicYear')
            ->where('academic_year_id', $period->academic_year_id)
            ->whereIn('id', $assignedClassroomIds)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $selectedClassroomId = $request->integer('classroom_id') ?: $classrooms->first()->id;
        abort_unless($assignedClassroomIds->contains($selectedClassroomId), 403, 'Anda bukan guru pembina pada kelas yang dipilih.');

        $classroom = $classrooms->firstWhere('id', $selectedClassroomId);
        $records = ExtracurricularRecord::query()
            ->with('student')
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->where('supervisor_id', $user->id)
            ->orderBy('student_id')
            ->orderBy('name')
            ->get()
            ->groupBy('student_id');

        return view('teacher.extracurricular', [
            'classroom' => $classroom,
            'classrooms' => $classrooms,
            'period' => $period,
            'records' => $records,
            'isLocked' => $classroom->isLockedInPeriod($period->id),
        ]);
    }

    public function update(ExtracurricularRequest $request): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');
        $classroom = Classroom::query()
            ->whereKey($request->validated('classroom_id'))
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Data ekstrakurikuler tidak dapat diubah lagi.');
        }

        $submitted = $request->validated('records', []);
        $userId = $request->user()->id;

        DB::transaction(function () use ($submitted, $period, $classroom, $userId): void {
            foreach ($submitted as $recordId => $record) {
                ExtracurricularRecord::query()
                    ->whereKey((int) $recordId)
                    ->where('period_id', $period->id)
                    ->where('classroom_id', $classroom->id)
                    ->where('supervisor_id', $userId)
                    ->update([
                        'predicate' => $record['predicate'],
                        'description' => filled($record['description'] ?? null)
                            ? trim($record['description'])
                            : null,
                    ]);
            }

            ActivityLogger::log(
                'save_extracurricular_assessment',
                "Menyimpan penilaian ekstrakurikuler kelas {$classroom->name}",
                $classroom
            );
        });

        return redirect()
            ->route('guru.ekstrakurikuler.index', ['classroom_id' => $classroom->id])
            ->with('success', 'Penilaian ekstrakurikuler berhasil disimpan.');
    }
}
