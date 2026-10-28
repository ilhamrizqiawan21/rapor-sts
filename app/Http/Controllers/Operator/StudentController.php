<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StudentRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\CompetencyOverride;
use App\Models\Enrollment;
use App\Models\HomeroomRecord;
use App\Models\MadrasahProfile;
use App\Models\Period;
use App\Models\StsScore;
use App\Models\Student;
use App\Models\SummativeScore;
use App\Services\ActivityLogger;
use App\Services\Import\StudentImporter;
use App\Services\Import\TemplateBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $activePeriod = (app()->bound('activePeriod') && app('activePeriod'))
            ? app('activePeriod')
            : Period::with('academicYear')->where('is_active', true)->first();
        $selectedYearId = $request->query('academic_year_id', $activePeriod?->academic_year_id);
        $selectedClassId = $request->query('classroom_id');
        $search = $request->query('search');

        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        $classrooms = Classroom::when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('level')
            ->orderBy('name')
            ->get();
        $selectedClass = $classrooms->firstWhere('id', (int) $selectedClassId);
        $selectedAcademicYear = $academicYears->firstWhere('id', (int) $selectedYearId);
        $profile = MadrasahProfile::first();
        $maximumClassLevel = $profile?->maximumClassLevel() ?? 12;
        $canPromote = $selectedClass && $selectedClass->level < $maximumClassLevel;
        $selectedYearStart = $selectedAcademicYear
            ? (int) explode('/', $selectedAcademicYear->name)[0]
            : null;
        $nextAcademicYear = $selectedYearStart
            ? $academicYears->firstWhere('name', ($selectedYearStart + 1).'/'.($selectedYearStart + 2))
            : null;
        $promotionAcademicYears = $nextAcademicYear ? collect([$nextAcademicYear]) : collect();
        $promotionClassrooms = Classroom::with('academicYear')
            ->when($nextAcademicYear, fn ($q) => $q->where('academic_year_id', $nextAcademicYear->id))
            ->when($selectedClass && $selectedClass->level < $maximumClassLevel, fn ($q) => $q->where('level', $selectedClass->level + 1))
            ->when(! $nextAcademicYear, fn ($q) => $q->whereKey(0))
            ->orderBy('academic_year_id')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $students = Student::with(['enrollments' => function ($q) use ($selectedYearId) {
            $q->when($selectedYearId, fn ($sub) => $sub->where('academic_year_id', $selectedYearId))->with('classroom');
        }])
            ->when($selectedClassId, function ($q, $classId) use ($selectedYearId) {
                $q->whereHas('enrollments', function ($sub) use ($classId, $selectedYearId) {
                    $sub->where('classroom_id', $classId)
                        ->when($selectedYearId, fn ($y) => $y->where('academic_year_id', $selectedYearId));
                });
            })
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('operator.students.index', compact(
            'students',
            'academicYears',
            'classrooms',
            'promotionClassrooms',
            'selectedYearId',
            'selectedClassId',
            'selectedClass',
            'promotionAcademicYears',
            'maximumClassLevel',
            'canPromote',
            'search'
        ));
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $classroomId = $data['classroom_id'] ?? null;
        unset($data['classroom_id']);

        $student = DB::transaction(function () use ($data, $classroomId) {
            $student = Student::create($data);

            if ($classroomId) {
                $classroom = Classroom::find($classroomId);
                if ($classroom) {
                    Enrollment::create([
                        'academic_year_id' => $classroom->academic_year_id,
                        'classroom_id' => $classroom->id,
                        'student_id' => $student->id,
                    ]);
                }
            }

            return $student;
        });

        ActivityLogger::log('create_student', "Menambahkan siswa {$student->name} (NISN: {$student->nisn})", $student);

        return redirect()->back()->with('success', "Data siswa {$student->name} berhasil ditambahkan.");
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();
        $classroomId = $data['classroom_id'] ?? null;
        unset($data['classroom_id']);

        DB::transaction(function () use ($student, $data, $classroomId) {
            $student->update($data);

            if ($classroomId) {
                $classroom = Classroom::find($classroomId);
                if ($classroom) {
                    Enrollment::updateOrCreate(
                        [
                            'academic_year_id' => $classroom->academic_year_id,
                            'student_id' => $student->id,
                        ],
                        [
                            'classroom_id' => $classroom->id,
                        ]
                    );
                }
            }
        });

        ActivityLogger::log('update_student', "Memperbarui data siswa {$student->name}", $student);

        return redirect()->back()->with('success', "Data siswa {$student->name} berhasil diperbarui.");
    }

    public function moveClass(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'source_classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'target_classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
        ]);

        $sourceClassroom = Classroom::findOrFail($validated['source_classroom_id']);
        $targetClassroom = Classroom::findOrFail($validated['target_classroom_id']);

        if ($sourceClassroom->academic_year_id !== (int) $validated['academic_year_id']) {
            return back()->withErrors(['source_classroom_id' => 'Kelas asal tidak sesuai tahun ajaran yang dipilih.']);
        }

        if ($targetClassroom->academic_year_id !== (int) $validated['academic_year_id']) {
            return back()->withErrors(['target_classroom_id' => 'Kelas tujuan harus berada pada tahun ajaran yang sama.']);
        }

        if ($sourceClassroom->id === $targetClassroom->id) {
            return back()->withErrors(['target_classroom_id' => 'Pilih kelas tujuan yang berbeda dari kelas asal.']);
        }

        $enrollment = Enrollment::query()
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('classroom_id', $sourceClassroom->id)
            ->where('student_id', $student->id)
            ->first();

        if (! $enrollment) {
            return back()->withErrors(['student_id' => 'Siswa tidak terdaftar pada kelas asal.']);
        }

        $enrollment->update(['classroom_id' => $targetClassroom->id]);

        ActivityLogger::log(
            'move_student',
            "Memindahkan siswa {$student->name} dari kelas {$sourceClassroom->name} ke {$targetClassroom->name}",
            $student
        );

        return redirect()->route('operator.students.index', [
            'academic_year_id' => $validated['academic_year_id'],
            'classroom_id' => $targetClassroom->id,
        ])->with('success', "Siswa {$student->name} berhasil dipindahkan ke {$targetClassroom->name}. Nilai dari kelas asal tetap tersimpan.");
    }

    public function promoteClass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source_classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'target_academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'target_classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
        ]);

        $sourceClassroom = Classroom::findOrFail($validated['source_classroom_id']);
        $sourceYear = AcademicYear::findOrFail($sourceClassroom->academic_year_id);
        $targetYear = AcademicYear::findOrFail($validated['target_academic_year_id']);
        $targetClassroom = Classroom::findOrFail($validated['target_classroom_id']);
        $profile = MadrasahProfile::first();
        $allowedLevels = $profile?->classLevels() ?? range(1, 12);

        if ($sourceClassroom->academic_year_id === $targetYear->id) {
            return back()->withErrors(['target_academic_year_id' => 'Pilih tahun ajaran yang berbeda dari tahun asal.']);
        }

        $sourceYearStart = (int) explode('/', $sourceYear->name)[0];
        $targetYearStart = (int) explode('/', $targetYear->name)[0];
        if ($targetYearStart !== $sourceYearStart + 1) {
            return back()->withErrors(['target_academic_year_id' => 'Tahun ajaran tujuan harus satu tahun setelah tahun asal.']);
        }

        if ($targetClassroom->academic_year_id !== $targetYear->id) {
            return back()->withErrors(['target_classroom_id' => 'Kelas tujuan harus berada pada tahun ajaran tujuan.']);
        }

        if (! in_array($sourceClassroom->level, $allowedLevels, true) || ! in_array($targetClassroom->level, $allowedLevels, true)) {
            return back()->withErrors(['target_classroom_id' => 'Tingkat kelas tidak sesuai dengan jenjang madrasah.']);
        }

        if ($targetClassroom->level !== $sourceClassroom->level + 1) {
            return back()->withErrors(['target_classroom_id' => 'Kelas tujuan harus satu tingkat di atas kelas asal.']);
        }

        $studentIds = Enrollment::query()
            ->where('academic_year_id', $sourceClassroom->academic_year_id)
            ->where('classroom_id', $sourceClassroom->id)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->pluck('student_id');

        $promotedCount = DB::transaction(function () use ($studentIds, $targetYear, $targetClassroom) {
            $alreadyEnrolledIds = Enrollment::query()
                ->where('academic_year_id', $targetYear->id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id');

            $studentIdsToPromote = $studentIds->diff($alreadyEnrolledIds);

            foreach ($studentIdsToPromote as $studentId) {
                Enrollment::create([
                    'academic_year_id' => $targetYear->id,
                    'classroom_id' => $targetClassroom->id,
                    'student_id' => $studentId,
                ]);
            }

            return $studentIdsToPromote->count();
        });

        $skippedCount = $studentIds->count() - $promotedCount;
        ActivityLogger::log(
            'promote_students',
            "Menaikkan {$promotedCount} siswa dari kelas {$sourceClassroom->name} ke {$targetClassroom->name} ({$targetYear->name})"
        );

        return redirect()->route('operator.students.index', [
            'academic_year_id' => $targetYear->id,
            'classroom_id' => $targetClassroom->id,
        ])->with('success', "Berhasil menaikkan {$promotedCount} siswa ke {$targetClassroom->name}. {$skippedCount} siswa dilewati karena sudah terdaftar pada tahun tujuan.");
    }

    public function toggleStatus(Student $student): RedirectResponse
    {
        $student->update(['is_active' => ! $student->is_active]);

        $statusText = $student->is_active ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLogger::log('toggle_student_status', "Status siswa {$student->name} {$statusText}", $student);

        return redirect()->back()->with('success', "Siswa {$student->name} berhasil {$statusText}.");
    }

    public function destroy(Student $student): RedirectResponse
    {
        $name = $student->name;

        DB::transaction(function () use ($student) {
            $student->summativeScores()->delete();
            $student->stsScores()->delete();
            $student->competencyOverrides()->delete();
            $student->homeroomRecords()->delete();
            $student->enrollments()->delete();
            $student->delete();
        });

        ActivityLogger::log('delete_student', "Menghapus siswa {$name} beserta seluruh nilainya");

        return redirect()->back()->with('success', "Data siswa {$name} berhasil dihapus.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:students,id'],
        ]);

        $ids = $validated['ids'];
        $count = count($ids);

        DB::transaction(function () use ($ids) {
            SummativeScore::whereIn('student_id', $ids)->delete();
            StsScore::whereIn('student_id', $ids)->delete();
            CompetencyOverride::whereIn('student_id', $ids)->delete();
            HomeroomRecord::whereIn('student_id', $ids)->delete();
            Enrollment::whereIn('student_id', $ids)->delete();
            Student::whereIn('id', $ids)->delete();
        });

        ActivityLogger::log('bulk_delete_students', "Menghapus {$count} data siswa beserta seluruh nilainya");

        return redirect()->route('operator.students.index')->with('success', "Berhasil menghapus {$count} data siswa.");
    }

    public function template(Request $request, TemplateBuilder $builder): StreamedResponse
    {
        $classroomId = $request->query('classroom_id');
        $classroom = $classroomId ? Classroom::find($classroomId) : null;

        $spreadsheet = $builder->buildStudentTemplate($classroom);

        $filename = 'Template_Import_Siswa_'.($classroom ? str_replace('.', '_', $classroom->name) : 'Semua').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request, StudentImporter $importer): RedirectResponse
    {
        $request->validate([
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ]);

        $classroom = Classroom::findOrFail($request->classroom_id);
        $result = $importer->import($request->file('file'), $classroom);

        if (! $result['success']) {
            return redirect()->back()->withErrors(['file' => implode('<br>', $result['errors'])]);
        }

        $msg = "Berhasil mengimpor {$result['imported']} data siswa ke kelas {$classroom->name}.";
        if (! empty($result['errors'])) {
            $msg .= ' Peringatan: '.implode('; ', $result['errors']);
        }

        return redirect()->route('operator.students.index', [
            'academic_year_id' => $classroom->academic_year_id,
            'classroom_id' => $classroom->id,
        ])->with('success', $msg);
    }
}
