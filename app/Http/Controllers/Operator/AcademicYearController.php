<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\AcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\Period;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::with(['periods' => fn ($q) => $q->orderBy('semester')])
            ->orderBy('name', 'desc')
            ->get();

        $activePeriod = Period::with('academicYear')->where('is_active', true)->first();

        return view('operator.lembaga.index', compact('academicYears', 'activePeriod'));
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        $year = AcademicYear::create($request->validated());

        // Otomatis buat periode Semester 1 (Ganjil) dan Semester 2 (Genap)
        Period::create([
            'academic_year_id' => $year->id,
            'semester' => 1,
            'is_active' => false,
        ]);
        Period::create([
            'academic_year_id' => $year->id,
            'semester' => 2,
            'is_active' => false,
        ]);

        ActivityLogger::log('create_academic_year', "Menambahkan tahun pelajaran {$year->name}", $year);

        return redirect()->route('operator.lembaga.index')->with('success', "Tahun pelajaran {$year->name} berhasil ditambahkan.");
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->classrooms()->exists()) {
            return redirect()->route('operator.lembaga.index')->with('error', 'Tahun pelajaran tidak dapat dihapus karena sudah memiliki data kelas.');
        }

        if ($academicYear->periods()->where('is_active', true)->exists()) {
            return redirect()->route('operator.lembaga.index')->with('error', 'Tahun pelajaran tidak dapat dihapus karena memuat periode aktif. Aktifkan periode lain terlebih dahulu.');
        }

        $name = $academicYear->name;
        $academicYear->delete();

        ActivityLogger::log('delete_academic_year', "Menghapus tahun pelajaran {$name}");

        return redirect()->route('operator.lembaga.index')->with('success', "Tahun pelajaran {$name} berhasil dihapus.");
    }
}
