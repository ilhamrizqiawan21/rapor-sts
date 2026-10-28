<?php

namespace App\Http\Controllers\Homeroom;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Homeroom\ExtracurricularRequest;
use App\Models\Classroom;
use App\Models\ExtracurricularRecord;
use App\Models\Period;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExtracurricularController extends Controller
{
    public function index(Request $request): View
    {
        $isOperator = $request->user()?->isOperator() ?? false;
        [$classroom, $period] = $this->classroomContext($isOperator ? $request->integer('classroom_id') : null);
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
            ->with('supervisor')
            ->orderBy('name')
            ->get()
            ->groupBy('student_id');

        return view('homeroom.extracurricular', [
            'classroom' => $classroom,
            'classrooms' => $isOperator
                ? Classroom::with('academicYear')->where('academic_year_id', $period->academic_year_id)->orderBy('level')->orderBy('name')->get()
                : collect([$classroom]),
            'period' => $period,
            'students' => $students,
            'records' => $records,
            'teachers' => User::query()
                ->where('role', Role::Guru->value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'isLocked' => $classroom->isLockedInPeriod($period->id),
            'isOperator' => $isOperator,
        ]);
    }

    public function update(ExtracurricularRequest $request): RedirectResponse
    {
        $isOperator = $request->user()?->isOperator() ?? false;
        [$classroom, $period] = $this->classroomContext($isOperator ? $request->integer('classroom_id') : null);
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
                        'supervisor_id' => (int) $record['supervisor_id'],
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
    private function classroomContext(?int $classroomId = null): array
    {
        $user = auth()->user();
        /** @var Period|null $period */
        $period = app()->bound('activePeriod') ? app('activePeriod') : null;

        if (! $user || ! $period) {
            abort(403, 'Periode aktif tidak ditemukan.');
        }

        $classroomQuery = Classroom::query()->where('academic_year_id', $period->academic_year_id);

        if ($user->isOperator()) {
            $classroomQuery
                ->when($classroomId, fn ($query) => $query->whereKey($classroomId))
                ->orderBy('level')
                ->orderBy('name');
        } else {
            abort(403, 'Penilaian ekstrakurikuler hanya dapat dikelola operator dan guru pembina.');
        }

        $classroom = $classroomQuery->first();

        if (! $classroom) {
            abort(403, 'Kelas tidak ditemukan pada periode aktif ini.');
        }

        return [$classroom, $period];
    }
}
