<?php

namespace App\Http\Controllers\Homeroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Homeroom\ExtracurricularRequest;
use App\Models\Classroom;
use App\Models\ExtracurricularRecord;
use App\Models\Period;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExtracurricularController extends Controller
{
    public function index(): View
    {
        [$classroom, $period] = $this->homeroomContext();
        $this->authorize('viewHomeroom', $classroom);

        $classroom->loadMissing([
            'enrollments' => fn ($q) => $q
                ->where('academic_year_id', $period->academic_year_id)
                ->with('student'),
        ]);

        $students = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($student) => $student !== null && $student->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $records = ExtracurricularRecord::query()
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->orderBy('name')
            ->get()
            ->groupBy('student_id');

        return view('homeroom.extracurricular', compact('classroom', 'period', 'students', 'records'));
    }

    public function update(ExtracurricularRequest $request): RedirectResponse
    {
        [$classroom, $period] = $this->homeroomContext();
        $this->authorize('updateHomeroom', $classroom);

        $submitted = $request->validated('extracurriculars', []);

        DB::transaction(function () use ($submitted, $classroom, $period) {
            ExtracurricularRecord::query()
                ->where('period_id', $period->id)
                ->where('classroom_id', $classroom->id)
                ->delete();

            foreach ($submitted as $studentId => $studentRecords) {
                foreach ($studentRecords as $record) {
                    $name = trim((string) ($record['name'] ?? ''));

                    if ($name === '') {
                        continue;
                    }

                    ExtracurricularRecord::create([
                        'period_id' => $period->id,
                        'classroom_id' => $classroom->id,
                        'student_id' => (int) $studentId,
                        'name' => $name,
                        'predicate' => $record['predicate'],
                        'description' => filled($record['description'] ?? null)
                            ? trim($record['description'])
                            : null,
                    ]);
                }
            }

            ActivityLogger::log(
                'save_extracurricular',
                "Menyimpan ekstrakurikuler kelas {$classroom->name}",
                $classroom
            );
        });

        return back()->with('success', 'Data ekstrakurikuler berhasil disimpan.');
    }

    /**
     * @return array{0: Classroom, 1: Period}
     */
    private function homeroomContext(): array
    {
        $user = auth()->user();
        /** @var Period|null $period */
        $period = app()->bound('activePeriod') ? app('activePeriod') : null;

        if (! $user || ! $period) {
            abort(403, 'Periode aktif tidak ditemukan.');
        }

        $classroom = Classroom::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('homeroom_teacher_id', $user->id)
            ->first();

        if (! $classroom) {
            abort(403, 'Anda bukan wali kelas pada periode aktif ini.');
        }

        return [$classroom, $period];
    }
}
