<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\Student;
use App\Services\Report\PdfRenderer;
use App\Services\Report\ReportCardBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportCardController extends Controller
{
    public function homeroomIndex(): View
    {
        $user = auth()->user();
        /** @var Period $period */
        $period = app('activePeriod');

        $classroom = Classroom::where('academic_year_id', $period->academic_year_id)
            ->where('homeroom_teacher_id', $user->id)
            ->first();

        if (! $classroom) {
            abort(403, 'Anda bukan wali kelas pada periode aktif ini.');
        }

        $this->authorize('print', $classroom);

        $classroom->loadMissing([
            'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
        ]);

        $students = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $isLocked = $classroom->isLockedInPeriod($period->id);

        return view('report.index', [
            'classroom' => $classroom,
            'students' => $students,
            'period' => $period,
            'isLocked' => $isLocked,
            'isHomeroom' => true,
            'classrooms' => collect([$classroom]),
        ]);
    }

    public function operatorIndex(Request $request): View
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $classrooms = Classroom::with('homeroomTeacher')
            ->where('academic_year_id', $period->academic_year_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $selectedClassroomId = $request->query('classroom_id', $classrooms->first()?->id);
        $classroom = $classrooms->firstWhere('id', (int) $selectedClassroomId) ?? $classrooms->first();

        $students = collect();
        $isLocked = false;

        if ($classroom) {
            $classroom->loadMissing([
                'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
            ]);

            $students = $classroom->enrollments
                ->pluck('student')
                ->filter(fn ($s) => $s !== null && $s->is_active)
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $isLocked = $classroom->isLockedInPeriod($period->id);
        }

        return view('report.index', [
            'classroom' => $classroom,
            'students' => $students,
            'period' => $period,
            'isLocked' => $isLocked,
            'isHomeroom' => false,
            'classrooms' => $classrooms,
        ]);
    }

    public function downloadSinglePdf(
        Classroom $classroom,
        Student $student,
        ReportCardBuilder $builder,
        PdfRenderer $renderer
    ): Response {
        $this->authorize('print', $classroom);

        /** @var Period $period */
        $period = app('activePeriod');

        $isEnrolled = $classroom->enrollments()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('student_id', $student->id)
            ->exists();
        abort_unless($isEnrolled, 404);

        $reportData = $builder->buildClassReportData($classroom, $period, $student);
        $paperSize = $reportData['printSetting']->paper_size ?? 'A4';

        $pdf = $renderer->render('pdf.report-card', $reportData, $paperSize, 'portrait');

        $cleanClass = preg_replace('/\s+/', '_', trim($classroom->name));
        $cleanStudent = preg_replace('/\s+/', '_', trim($student->name));
        $cleanNisn = preg_replace('/\s+/', '_', trim($student->nisn));
        $filename = "Rapor_STS_{$cleanClass}_{$cleanStudent}_{$cleanNisn}.pdf";

        return $pdf->download($filename);
    }

    public function downloadAllPdf(
        Classroom $classroom,
        ReportCardBuilder $builder,
        PdfRenderer $renderer
    ): Response {
        $this->authorize('print', $classroom);

        /** @var Period $period */
        $period = app('activePeriod');

        $reportData = $builder->buildClassReportData($classroom, $period);
        $paperSize = $reportData['printSetting']->paper_size ?? 'A4';

        $pdf = $renderer->render('pdf.report-card', $reportData, $paperSize, 'portrait');

        $cleanClass = preg_replace('/\s+/', '_', trim($classroom->name));
        $filename = "Rapor_STS_{$cleanClass}_semua.pdf";

        return $pdf->download($filename);
    }
}
