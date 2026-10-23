<?php

namespace App\Http\Controllers\Kepala;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\MadrasahProfile;
use App\Models\Period;
use App\Models\PrintSetting;
use App\Services\Export\HeadmasterRecapExporter;
use App\Services\Headmaster\DashboardAnalytics;
use App\Services\Report\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $classrooms = Classroom::where('academic_year_id', $period->academic_year_id)
            ->with(['homeroomTeacher'])
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $selectedLevel = $request->query('tingkat');
        if ($selectedLevel !== null && $selectedLevel !== '') {
            $filteredClassrooms = $classrooms->where('level', (int) $selectedLevel)->values();
        } else {
            $filteredClassrooms = $classrooms;
        }

        $selectedClassroomId = $request->query('classroom_id', $filteredClassrooms->first()?->id);
        $classroom = $filteredClassrooms->firstWhere('id', (int) $selectedClassroomId) ?? $filteredClassrooms->first();

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

        return view('kepala.report', [
            'classrooms' => $filteredClassrooms,
            'allClassrooms' => $classrooms,
            'selectedClassroom' => $classroom,
            'students' => $students,
            'period' => $period,
            'isLocked' => $isLocked,
        ]);
    }

    public function downloadExcel(HeadmasterRecapExporter $exporter): StreamedResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $spreadsheet = $exporter->export($period);
        $sem = $period->semester->value;
        $cleanYear = preg_replace('/[^A-Za-z0-9]/', '_', $period->academicYear->name);
        $filename = "Rekap_Nilai_Madrasah_{$sem}_{$cleanYear}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadPdf(PdfRenderer $renderer, DashboardAnalytics $analytics): Response
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $dashboardData = $analytics->getDashboardData($period);
        $profile = MadrasahProfile::first() ?? new MadrasahProfile;
        $printSetting = PrintSetting::firstOrNew(
            ['period_id' => $period->id],
            [
                'report_title' => 'LAPORAN HASIL SUMATIF TENGAH SEMESTER',
                'print_place' => $profile->regency ?? '-',
                'print_date' => now()->toDateString(),
                'score_label' => 'Nilai Akhir',
                'paper_size' => 'A4',
            ]
        );

        $pdf = $renderer->render('pdf.headmaster-recap', array_merge($dashboardData, [
            'profile' => $profile,
            'printSetting' => $printSetting,
        ]), 'A4', 'landscape');

        $sem = $period->semester->value;
        $cleanYear = preg_replace('/[^A-Za-z0-9]/', '_', $period->academicYear->name);
        $filename = "Rekap_Nilai_Madrasah_{$sem}_{$cleanYear}.pdf";

        return $pdf->download($filename);
    }
}
