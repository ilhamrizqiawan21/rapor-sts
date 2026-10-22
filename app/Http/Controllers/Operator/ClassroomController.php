<?php

namespace App\Http\Controllers\Operator;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\ClassroomBatchRequest;
use App\Http\Requests\Operator\ClassroomRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(Request $request): View
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $selectedYearId = $request->query('academic_year_id', $academicYears->first()?->id);

        $classrooms = Classroom::with(['homeroomTeacher', 'academicYear'])
            ->withCount('enrollments')
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $teachers = User::where('role', Role::Guru)->where('is_active', true)->orderBy('name')->get();

        return view('operator.classrooms.index', compact('classrooms', 'academicYears', 'selectedYearId', 'teachers'));
    }

    public function store(ClassroomRequest $request): RedirectResponse
    {
        $classroom = Classroom::create($request->validated());

        ActivityLogger::log('create_classroom', "Menambahkan kelas {$classroom->name}", $classroom);

        return redirect()->route('operator.classrooms.index', ['academic_year_id' => $classroom->academic_year_id])
            ->with('success', "Kelas {$classroom->name} berhasil ditambahkan.");
    }

    public function batchStore(ClassroomBatchRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $yearId = $validated['academic_year_id'];
        $level = $validated['level'];
        $prefix = trim($validated['prefix']);
        $curriculum = $validated['curriculum'];
        $phase = $validated['phase'] ?? 'D';

        // Parsing daftar paralel (misal: "A, B, C, D" atau "A;B;C")
        $parallels = preg_split('/[\s,;]+/', $validated['parallels'], -1, PREG_SPLIT_NO_EMPTY);

        $createdCount = 0;
        DB::transaction(function () use ($yearId, $level, $prefix, $curriculum, $phase, $parallels, &$createdCount) {
            foreach ($parallels as $p) {
                $className = $prefix.strtoupper(trim($p));

                $exists = Classroom::where('academic_year_id', $yearId)
                    ->where('name', $className)
                    ->exists();

                if (! $exists) {
                    Classroom::create([
                        'academic_year_id' => $yearId,
                        'level' => $level,
                        'name' => $className,
                        'alias' => "Kelas {$className}",
                        'curriculum' => $curriculum,
                        'phase' => $phase,
                    ]);
                    $createdCount++;
                }
            }
        });

        ActivityLogger::log('batch_create_classrooms', "Menambahkan {$createdCount} kelas secara serentak");

        return redirect()->route('operator.classrooms.index', ['academic_year_id' => $yearId])
            ->with('success', "Berhasil menambahkan {$createdCount} kelas sekaligus.");
    }

    public function update(ClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        ActivityLogger::log('update_classroom', "Memperbarui kelas {$classroom->name}", $classroom);

        return redirect()->route('operator.classrooms.index', ['academic_year_id' => $classroom->academic_year_id])
            ->with('success', "Kelas {$classroom->name} berhasil diperbarui.");
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        if ($classroom->enrollments()->exists() || $classroom->teachingAssignments()->exists()) {
            return redirect()->back()->with('error', 'Kelas tidak dapat dihapus karena sudah memiliki siswa atau pembagian mengajar.');
        }

        $name = $classroom->name;
        $yearId = $classroom->academic_year_id;
        $classroom->delete();

        ActivityLogger::log('delete_classroom', "Menghapus kelas {$name}");

        return redirect()->route('operator.classrooms.index', ['academic_year_id' => $yearId])
            ->with('success', "Kelas {$name} berhasil dihapus.");
    }
}
