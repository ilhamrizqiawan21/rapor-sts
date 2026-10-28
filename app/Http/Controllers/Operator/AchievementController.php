<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\AchievementRequest;
use App\Models\AchievementRecord;
use App\Models\Classroom;
use App\Models\Period;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Period|null $period */
        $period = app()->bound('activePeriod') ? app('activePeriod') : Period::with('academicYear')->where('is_active', true)->first();

        abort_unless($period, 404, 'Periode aktif tidak ditemukan.');

        $classrooms = Classroom::query()
            ->with('academicYear')
            ->where('academic_year_id', $period->academic_year_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();
        $selectedClassroomId = (int) $request->query('classroom_id', $classrooms->first()?->id);
        $classroom = $classrooms->firstWhere('id', $selectedClassroomId);
        $students = collect();
        $records = collect();
        $isLocked = false;

        if ($classroom) {
            $isLocked = $classroom->isLockedInPeriod($period->id);
            $classroom->loadMissing([
                'enrollments' => fn ($query) => $query
                    ->where('academic_year_id', $period->academic_year_id)
                    ->with('student'),
            ]);

            $students = $classroom->enrollments
                ->pluck('student')
                ->filter(fn ($student) => $student !== null && $student->is_active)
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $records = AchievementRecord::query()
                ->where('period_id', $period->id)
                ->where('classroom_id', $classroom->id)
                ->orderByDesc('achieved_at')
                ->orderBy('name')
                ->get()
                ->groupBy('student_id');
        }

        return view('operator.achievements.index', compact(
            'period',
            'classrooms',
            'classroom',
            'students',
            'records',
            'isLocked'
        ));
    }

    public function update(AchievementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        /** @var Period $period */
        $period = app('activePeriod');
        $classroom = Classroom::query()
            ->whereKey($validated['classroom_id'])
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Data prestasi tidak dapat diubah lagi kecuali kunci dibuka.');
        }

        $submitted = $validated['achievements'] ?? [];

        DB::transaction(function () use ($submitted, $classroom, $period): void {
            AchievementRecord::query()
                ->where('period_id', $period->id)
                ->where('classroom_id', $classroom->id)
                ->delete();

            foreach ($submitted as $studentId => $studentRecords) {
                foreach ($studentRecords as $record) {
                    $name = trim((string) ($record['name'] ?? ''));

                    if ($name === '') {
                        continue;
                    }

                    AchievementRecord::create([
                        'period_id' => $period->id,
                        'classroom_id' => $classroom->id,
                        'student_id' => (int) $studentId,
                        'name' => $name,
                        'level' => $record['level'],
                        'rank' => filled($record['rank'] ?? null) ? trim($record['rank']) : null,
                        'achieved_at' => $record['achieved_at'] ?? null,
                        'organizer' => filled($record['organizer'] ?? null) ? trim($record['organizer']) : null,
                        'description' => filled($record['description'] ?? null) ? trim($record['description']) : null,
                    ]);
                }
            }

            ActivityLogger::log(
                'save_achievements',
                "Menyimpan prestasi siswa kelas {$classroom->name}",
                $classroom
            );
        });

        return redirect()
            ->route('operator.achievements.index', ['classroom_id' => $classroom->id])
            ->with('success', 'Data prestasi siswa berhasil disimpan.');
    }
}
