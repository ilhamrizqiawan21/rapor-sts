<?php

namespace App\Http\Controllers\Homeroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\AchievementRequest;
use App\Models\AchievementRecord;
use App\Models\Classroom;
use App\Models\Period;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AchievementController extends Controller
{
    protected function getHomeroomClassroom(): Classroom
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

        return $classroom;
    }

    public function index(): View
    {
        $classroom = $this->getHomeroomClassroom();
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('viewAchievements', $classroom);

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

        return view('homeroom.achievements', [
            'period' => $period,
            'classroom' => $classroom,
            'students' => $students,
            'records' => $records,
            'isLocked' => $classroom->isLockedInPeriod($period->id),
        ]);
    }

    public function update(AchievementRequest $request): RedirectResponse
    {
        $classroom = $this->getHomeroomClassroom();
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('updateAchievements', [$classroom, $period]);

        if ((int) $request->validated('classroom_id') !== $classroom->id) {
            abort(403, 'Kelas tidak sesuai dengan kelas wali Anda.');
        }

        $submitted = $request->validated('achievements', []);

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
            ->route('wali.prestasi.index')
            ->with('success', 'Data prestasi siswa berhasil disimpan.');
    }
}
