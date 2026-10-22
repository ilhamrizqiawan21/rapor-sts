<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StudentRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;
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
            'selectedYearId',
            'selectedClassId',
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

    public function toggleStatus(Student $student): RedirectResponse
    {
        $student->update(['is_active' => ! $student->is_active]);

        $statusText = $student->is_active ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLogger::log('toggle_student_status', "Status siswa {$student->name} {$statusText}", $student);

        return redirect()->back()->with('success', "Siswa {$student->name} berhasil {$statusText}.");
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
