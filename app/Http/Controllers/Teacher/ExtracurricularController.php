<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ExtracurricularRequest;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivityDefinition;
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

        $activities = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $user->id)
            ->orderBy('name')
            ->get();

        if ($activities->isEmpty()) {
            return $this->legacyIndex($request, $period);
        }

        $classrooms = Classroom::query()
            ->with('academicYear')
            ->where('academic_year_id', $period->academic_year_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        abort_if($classrooms->isEmpty(), 403, 'Belum ada kelas pada periode aktif.');

        $selectedActivityId = $request->integer('activity_id') ?: $activities->first()->id;
        abort_unless($activities->contains('id', $selectedActivityId), 403, 'Anda bukan guru pembina kegiatan ini.');

        $selectedClassroomId = $request->integer('classroom_id') ?: $classrooms->first()->id;
        abort_unless($classrooms->contains('id', $selectedClassroomId), 403, 'Kelas tidak berada pada periode aktif.');

        $classroom = $classrooms->firstWhere('id', $selectedClassroomId);
        $records = ExtracurricularRecord::query()
            ->with('student')
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->where('activity_definition_id', $selectedActivityId)
            ->orderBy('student_id')
            ->get()
            ->keyBy('student_id');

        $enrolledStudents = Enrollment::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('classroom_id', $classroom->id)
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter(fn ($student) => $student !== null && $student->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('teacher.extracurricular-modern', [
            'activities' => $activities,
            'classroom' => $classroom,
            'classrooms' => $classrooms,
            'period' => $period,
            'records' => $records,
            'availableStudents' => $enrolledStudents->reject(fn ($student) => $records->has($student->id))->values(),
            'selectedActivityId' => $selectedActivityId,
            'isLocked' => $classroom->isLockedInPeriod($period->id),
        ]);
    }

    public function update(ExtracurricularRequest $request): RedirectResponse
    {
        if (! $request->filled('activity_id')) {
            return $this->legacyUpdate($request);
        }

        /** @var Period $period */
        $period = app('activePeriod');
        $userId = $request->user()->id;
        $validated = $request->validated();
        $activity = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $userId)
            ->findOrFail((int) $validated['activity_id']);
        $classroom = Classroom::query()
            ->whereKey($validated['classroom_id'])
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Data ekstrakurikuler tidak dapat diubah lagi.');
        }

        $participantIds = collect($validated['participant_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $submittedRecords = $validated['records'] ?? [];

        DB::transaction(function () use ($activity, $classroom, $period, $participantIds, $submittedRecords): void {
            $recordsQuery = ExtracurricularRecord::query()
                ->where('period_id', $period->id)
                ->where('classroom_id', $classroom->id)
                ->where('activity_definition_id', $activity->id);

            if ($participantIds->isEmpty()) {
                $recordsQuery->delete();
            } else {
                $recordsQuery->whereNotIn('student_id', $participantIds)->delete();
            }

            foreach ($participantIds as $studentId) {
                $record = ExtracurricularRecord::query()
                    ->where('period_id', $period->id)
                    ->where('classroom_id', $classroom->id)
                    ->where('student_id', $studentId)
                    ->where('activity_definition_id', $activity->id)
                    ->first() ?? new ExtracurricularRecord;

                $record->fill([
                    'period_id' => $period->id,
                    'classroom_id' => $classroom->id,
                    'student_id' => $studentId,
                    'supervisor_id' => $activity->supervisor_id,
                    'activity_definition_id' => $activity->id,
                    'name' => $activity->name,
                    'predicate' => $submittedRecords[$studentId]['grade'],
                    'description' => filled($submittedRecords[$studentId]['description'] ?? null)
                        ? trim($submittedRecords[$studentId]['description'])
                        : null,
                ])->save();
            }

            ActivityLogger::log(
                'save_extracurricular_assessment',
                "Menyimpan peserta dan nilai {$activity->name} kelas {$classroom->name}",
                $classroom
            );
        });

        return redirect()
            ->route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
            ])
            ->with('success', 'Peserta dan nilai ekstrakurikuler berhasil disimpan.');
    }

    private function legacyIndex(Request $request, Period $period): View
    {
        $user = $request->user();
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

    private function legacyUpdate(ExtracurricularRequest $request): RedirectResponse
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
        });

        return redirect()
            ->route('guru.ekstrakurikuler.index', ['classroom_id' => $classroom->id])
            ->with('success', 'Penilaian ekstrakurikuler berhasil disimpan.');
    }
}
