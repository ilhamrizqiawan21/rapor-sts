<?php

namespace App\Http\Controllers\Operator;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\TeacherRequest;
use App\Models\Classroom;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Import\TeacherImporter;
use App\Services\Import\TemplateBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $teachers = User::where('role', Role::Guru)
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('operator.teachers.index', compact('teachers', 'search'));
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['role'] = Role::Guru;
        $data['password_plain'] = $data['password'];
        $data['password'] = Hash::make($data['password']);

        $teacher = User::create($data);

        ActivityLogger::log('create_teacher', "Menambahkan data guru {$teacher->name}", $teacher);

        return redirect()->route('operator.teachers.index')->with('success', "Data guru {$teacher->name} berhasil ditambahkan.");
    }

    public function update(TeacherRequest $request, User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === Role::Guru, 404);

        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password_plain'] = $data['password'];
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $teacher->update($data);

        ActivityLogger::log('update_teacher', "Memperbarui data guru {$teacher->name}", $teacher);

        return redirect()->route('operator.teachers.index')->with('success', "Data guru {$teacher->name} berhasil diperbarui.");
    }

    public function toggleStatus(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === Role::Guru, 404);

        $teacher->update(['is_active' => ! $teacher->is_active]);

        $statusText = $teacher->is_active ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLogger::log('toggle_teacher_status', "Status guru {$teacher->name} {$statusText}", $teacher);

        return redirect()->back()->with('success', "Guru {$teacher->name} berhasil {$statusText}.");
    }

    public function destroy(User $teacher): RedirectResponse
    {
        abort_unless($teacher->role === Role::Guru, 404);

        $inUse = TeachingAssignment::where('teacher_id', $teacher->id)->exists()
            || Classroom::where('homeroom_teacher_id', $teacher->id)->exists();

        if ($inUse) {
            return redirect()->back()->withErrors([
                'delete' => "Guru {$teacher->name} tidak dapat dihapus karena masih memiliki data mengajar atau menjadi wali kelas. Hapus data mengajar / ganti wali kelas terlebih dahulu, atau gunakan Nonaktifkan.",
            ]);
        }

        $teacher->delete();

        ActivityLogger::log('delete_teacher', "Menghapus data guru {$teacher->name}");

        return redirect()->back()->with('success', "Data guru {$teacher->name} berhasil dihapus.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = $validated['ids'];

        $hasNonGuru = User::whereIn('id', $ids)->where('role', '!=', Role::Guru)->exists();
        if ($hasNonGuru) {
            return redirect()->back()->withErrors([
                'delete' => 'Hanya pengguna dengan peran Guru yang dapat dihapus melalui menu ini.',
            ]);
        }

        $inUse = TeachingAssignment::whereIn('teacher_id', $ids)->exists()
            || Classroom::whereIn('homeroom_teacher_id', $ids)->exists();

        if ($inUse) {
            return redirect()->back()->withErrors([
                'delete' => 'Guru yang dipilih tidak dapat dihapus karena masih memiliki data mengajar atau menjadi wali kelas. Hapus data mengajar / ganti wali kelas terlebih dahulu, atau gunakan Nonaktifkan.',
            ]);
        }

        $count = count($ids);

        DB::transaction(function () use ($ids) {
            User::whereIn('id', $ids)->where('role', Role::Guru)->delete();
        });

        ActivityLogger::log('bulk_delete_teachers', "Menghapus {$count} data guru");

        return redirect()->route('operator.teachers.index')->with('success', "Berhasil menghapus {$count} data guru.");
    }

    public function template(TemplateBuilder $builder): StreamedResponse
    {
        $spreadsheet = $builder->buildTeacherTemplate();

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template_Import_Guru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request, TeacherImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ]);

        $result = $importer->import($request->file('file'));

        if (! $result['success']) {
            return redirect()->back()->withErrors(['file' => implode('<br>', $result['errors'])]);
        }

        $msg = "Berhasil mengimpor {$result['imported']} data guru.";
        if (! empty($result['errors'])) {
            $msg .= ' Peringatan: '.implode('; ', $result['errors']);
        }

        return redirect()->route('operator.teachers.index')->with('success', $msg);
    }
}
