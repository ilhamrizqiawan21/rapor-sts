<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ExtracurricularRequest;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivityDefinition;
use App\Models\ExtracurricularRecord;
use App\Models\Period;
use App\Models\Student;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExtracurricularController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        /** @var Period|null $period */
        $period = app()->bound('activePeriod') ? app('activePeriod') : null;

        abort_unless($period, 404, 'Periode aktif tidak ditemukan.');

        $activities = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $user->id)
            ->orderBy('name')
            ->get();

        if ($activities->isEmpty()) {
            return $this->legacyIndex($request, $period);
        }

        $classrooms = Classroom::query()
            ->with('academicYear')
            ->where('academic_year_id', $period->academic_year_id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        abort_if($classrooms->isEmpty(), 403, 'Belum ada kelas pada periode aktif.');

        $selectedActivityId = $request->integer('activity_id') ?: $activities->first()->id;
        abort_unless($activities->contains('id', $selectedActivityId), 403, 'Anda bukan guru pembina kegiatan ini.');
        $selectedActivity = $activities->firstWhere('id', $selectedActivityId);

        // Grouping & Filtering by Tingkat (Level) and Kelas
        $levels = $classrooms->pluck('level')->unique()->sort()->values();

        $selectedLevel = null;
        if ($request->filled('level')) {
            $selectedLevel = $request->integer('level');
        } elseif ($request->filled('classroom_id')) {
            $selectedLevel = $classrooms->firstWhere('id', $request->integer('classroom_id'))?->level;
        }

        if (! $selectedLevel || ! $levels->contains($selectedLevel)) {
            $selectedLevel = $levels->first();
        }

        $filteredClassrooms = $classrooms->where('level', $selectedLevel)->values();
        if ($filteredClassrooms->isEmpty()) {
            $filteredClassrooms = $classrooms;
        }

        $selectedClassroomId = $request->integer('classroom_id');
        $classroom = $filteredClassrooms->firstWhere('id', $selectedClassroomId) ?: $filteredClassrooms->first();
        abort_unless($classroom, 403, 'Kelas tidak berada pada periode aktif.');

        $records = ExtracurricularRecord::query()
            ->with('student')
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->where('activity_definition_id', $selectedActivityId)
            ->orderBy('student_id')
            ->get()
            ->keyBy('student_id');

        $enrolledStudents = Enrollment::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('classroom_id', $classroom->id)
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter(fn ($student) => $student !== null && $student->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $isMandatory = $selectedActivity->isMandatory();

        // Participants list
        if ($isMandatory) {
            // Wajib: Semua siswa otomatis peserta
            $participants = $enrolledStudents;
            $availableStudents = collect();
        } else {
            // Pilihan: Hanya siswa yang didaftarkan sebagai anggota
            $participants = $enrolledStudents->filter(fn ($student) => $records->has($student->id))->values();
            $availableStudents = $enrolledStudents->reject(fn ($student) => $records->has($student->id))->values();
        }

        return view('teacher.extracurricular-modern', [
            'activities' => $activities,
            'selectedActivity' => $selectedActivity,
            'selectedActivityId' => $selectedActivityId,
            'levels' => $levels,
            'selectedLevel' => $selectedLevel,
            'classroom' => $classroom,
            'classrooms' => $classrooms,
            'filteredClassrooms' => $filteredClassrooms,
            'period' => $period,
            'records' => $records,
            'participants' => $participants,
            'availableStudents' => $availableStudents,
            'enrolledStudents' => $enrolledStudents,
            'isMandatory' => $isMandatory,
            'isLocked' => $classroom->isLockedInPeriod($period->id),
        ]);
    }

    public function updateMembers(Request $request): RedirectResponse
    {
        $period = app('activePeriod');
        $userId = $request->user()->id;

        $validated = $request->validate([
            'activity_id' => ['required', 'integer', 'exists:extracurricular_activity_definitions,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:students,id'],
        ]);

        $activity = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $userId)
            ->findOrFail((int) $validated['activity_id']);

        $classroom = Classroom::query()
            ->whereKey($validated['classroom_id'])
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Data ekstrakurikuler tidak dapat diubah lagi.');
        }

        $memberIds = collect($validated['member_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($activity, $classroom, $period, $memberIds): void {
            // Delete removed members
            $deleteQuery = ExtracurricularRecord::query()
                ->where('period_id', $period->id)
                ->where('classroom_id', $classroom->id)
                ->where('activity_definition_id', $activity->id);

            if ($memberIds->isNotEmpty()) {
                $deleteQuery->whereNotIn('student_id', $memberIds)->delete();
            } else {
                $deleteQuery->delete();
            }

            // Upsert / initialize records for members
            foreach ($memberIds as $studentId) {
                $existing = ExtracurricularRecord::query()
                    ->where('period_id', $period->id)
                    ->where('classroom_id', $classroom->id)
                    ->where('student_id', $studentId)
                    ->where('activity_definition_id', $activity->id)
                    ->first();

                if (! $existing) {
                    ExtracurricularRecord::create([
                        'period_id' => $period->id,
                        'classroom_id' => $classroom->id,
                        'student_id' => $studentId,
                        'supervisor_id' => $activity->supervisor_id,
                        'activity_definition_id' => $activity->id,
                        'name' => $activity->name,
                        'predicate' => null,
                        'description' => null,
                    ]);
                }
            }

            ActivityLogger::log(
                'update_extracurricular_members',
                "Memperbarui anggota {$activity->name} kelas {$classroom->name}",
                $classroom
            );
        });

        return redirect()
            ->route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'level' => $classroom->level,
            ])
            ->with('success', 'Daftar anggota ekstrakurikuler berhasil diperbarui.');
    }

    public function update(ExtracurricularRequest $request): RedirectResponse
    {
        if (! $request->filled('activity_id')) {
            return $this->legacyUpdate($request);
        }

        /** @var Period $period */
        $period = app('activePeriod');
        $userId = $request->user()->id;
        $validated = $request->validated();
        $activity = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $userId)
            ->findOrFail((int) $validated['activity_id']);
        $classroom = Classroom::query()
            ->whereKey($validated['classroom_id'])
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Data ekstrakurikuler tidak dapat diubah lagi.');
        }

        $participantIds = collect($validated['participant_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($activity->isMandatory() && $participantIds->isEmpty()) {
            $participantIds = Enrollment::query()
                ->where('academic_year_id', $period->academic_year_id)
                ->where('classroom_id', $classroom->id)
                ->whereHas('student', fn ($q) => $q->where('is_active', true))
                ->pluck('student_id');
        }

        $submittedRecords = $validated['records'] ?? [];

        DB::transaction(function () use ($activity, $classroom, $period, $participantIds, $submittedRecords): void {
            $recordsQuery = ExtracurricularRecord::query()
                ->where('period_id', $period->id)
                ->where('classroom_id', $classroom->id)
                ->where('activity_definition_id', $activity->id);

            if ($participantIds->isEmpty()) {
                $recordsQuery->delete();
            } else {
                $recordsQuery->whereNotIn('student_id', $participantIds)->delete();
            }

            foreach ($participantIds as $studentId) {
                $gradeInput = $submittedRecords[$studentId]['grade'] ?? $submittedRecords[$studentId]['predicate'] ?? null;

                $grade = match ($gradeInput) {
                    'Sangat Baik' => 'A',
                    'Baik' => 'B',
                    'Cukup' => 'C',
                    'Kurang' => 'D',
                    default => in_array($gradeInput, ['A', 'B', 'C', 'D'], true) ? $gradeInput : 'B',
                };

                $record = ExtracurricularRecord::query()
                    ->where('period_id', $period->id)
                    ->where('classroom_id', $classroom->id)
                    ->where('student_id', $studentId)
                    ->where('activity_definition_id', $activity->id)
                    ->first() ?? new ExtracurricularRecord;

                $record->fill([
                    'period_id' => $period->id,
                    'classroom_id' => $classroom->id,
                    'student_id' => $studentId,
                    'supervisor_id' => $activity->supervisor_id,
                    'activity_definition_id' => $activity->id,
                    'name' => $activity->name,
                    'predicate' => $grade,
                    'description' => filled($submittedRecords[$studentId]['description'] ?? null)
                        ? trim($submittedRecords[$studentId]['description'])
                        : null,
                ])->save();
            }

            ActivityLogger::log(
                'save_extracurricular_assessment',
                "Menyimpan peserta dan nilai {$activity->name} kelas {$classroom->name}",
                $classroom
            );
        });

        return redirect()
            ->route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'level' => $classroom->level,
            ])
            ->with('success', 'Peserta dan nilai ekstrakurikuler berhasil disimpan.');
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');
        $userId = $request->user()->id;

        $activity = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $userId)
            ->findOrFail($request->integer('activity_id'));

        $classroom = Classroom::query()
            ->whereKey($request->integer('classroom_id'))
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        $enrolledStudents = Enrollment::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('classroom_id', $classroom->id)
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $records = ExtracurricularRecord::query()
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->where('activity_definition_id', $activity->id)
            ->get()
            ->keyBy('student_id');

        // Target students
        $students = $activity->isMandatory()
            ? $enrolledStudents
            : $enrolledStudents->filter(fn ($s) => $records->has($s->id))->values();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai Ekskul');

        // Header info
        $sheet->setCellValue('A1', 'FORMAT PENILAIAN EKSTRAKURIKULER RDM');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->setCellValue('A2', 'Kegiatan: '.$activity->name.' ('.$activity->typeLabel().')');
        $sheet->setCellValue('A3', 'Kelas: '.$classroom->name);
        $sheet->setCellValue('A4', 'Tahun Pelajaran: '.$period->academicYear->name.' · Semester '.$period->semester->label());
        $sheet->getStyle('A2:A4')->getFont()->setBold(true);

        // Table headers
        $startRow = 6;
        $headers = ['No', 'NISN', 'Nama Siswa', 'L/P', 'Nilai (A/B/C/D)', 'Keterangan'];
        foreach ($headers as $colIdx => $header) {
            $colLetter = chr(65 + $colIdx);
            $sheet->setCellValue($colLetter.$startRow, $header);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B5998'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle("A{$startRow}:F{$startRow}")->applyFromArray($headerStyle);
        $sheet->getRowDimension($startRow)->setRowHeight(24);

        // Populate rows
        $currentRow = $startRow + 1;
        foreach ($students as $index => $student) {
            $record = $records->get($student->id);
            $grade = $record?->predicate ?? '';
            $description = $record?->description ?? '';

            $sheet->setCellValue('A'.$currentRow, $index + 1);
            $sheet->setCellValueExplicit('B'.$currentRow, (string) $student->nisn, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C'.$currentRow, $student->name);
            $sheet->setCellValue('D'.$currentRow, $student->gender ?? '-');
            $sheet->setCellValue('E'.$currentRow, $grade);
            $sheet->setCellValue('F'.$currentRow, $description);

            $currentRow++;
        }

        $lastDataRow = max($currentRow - 1, $startRow);
        $sheet->getStyle("A{$startRow}:F{$lastDataRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A7:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B7:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D7:D{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E7:E{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Autosize columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'RDM_Ekskul_'.str_replace(' ', '_', $activity->name).'_'.str_replace(['.', ' '], '_', $classroom->name).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');
        $userId = $request->user()->id;

        $request->validate([
            'activity_id' => ['required', 'integer', 'exists:extracurricular_activity_definitions,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'template_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $activity = ExtracurricularActivityDefinition::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $userId)
            ->findOrFail($request->integer('activity_id'));

        $classroom = Classroom::query()
            ->whereKey($request->integer('classroom_id'))
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Data ekstrakurikuler tidak dapat diubah lagi.');
        }

        $file = $request->file('template_file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // Enrolled active students keyed by nisn
        $studentsByNisn = Enrollment::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('classroom_id', $classroom->id)
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->keyBy(fn ($s) => trim((string) $s->nisn));

        $updatedCount = 0;

        DB::transaction(function () use ($sheet, $highestRow, $studentsByNisn, $period, $classroom, $activity, &$updatedCount): void {
            // Find start row: look for header 'NISN'
            $dataStartRow = 7;
            for ($r = 1; $r <= min(15, $highestRow); $r++) {
                $val = trim((string) $sheet->getCell('B'.$r)->getValue());
                if (strtoupper($val) === 'NISN') {
                    $dataStartRow = $r + 1;
                    break;
                }
            }

            for ($row = $dataStartRow; $row <= $highestRow; $row++) {
                $nisn = trim((string) $sheet->getCell('B'.$row)->getValue());
                if ($nisn === '' || ! $studentsByNisn->has($nisn)) {
                    continue;
                }

                $student = $studentsByNisn->get($nisn);
                $gradeInput = trim((string) $sheet->getCell('E'.$row)->getValue());
                $description = trim((string) $sheet->getCell('F'.$row)->getValue());

                if ($gradeInput === '') {
                    continue;
                }

                $grade = match (strtoupper($gradeInput)) {
                    'SANGAT BAIK' => 'A',
                    'BAIK' => 'B',
                    'CUKUP' => 'C',
                    'KURANG' => 'D',
                    default => in_array(strtoupper($gradeInput), ['A', 'B', 'C', 'D'], true) ? strtoupper($gradeInput) : null,
                };

                if (! $grade) {
                    continue;
                }

                ExtracurricularRecord::updateOrCreate([
                    'period_id' => $period->id,
                    'classroom_id' => $classroom->id,
                    'student_id' => $student->id,
                    'activity_definition_id' => $activity->id,
                ], [
                    'supervisor_id' => $activity->supervisor_id,
                    'name' => $activity->name,
                    'predicate' => $grade,
                    'description' => $description !== '' ? $description : null,
                ]);

                $updatedCount++;
            }

            ActivityLogger::log(
                'import_extracurricular_assessment',
                "Mengimpor {$updatedCount} nilai {$activity->name} kelas {$classroom->name} dari Excel",
                $classroom
            );
        });

        return redirect()
            ->route('guru.ekstrakurikuler.index', [
                'activity_id' => $activity->id,
                'classroom_id' => $classroom->id,
                'level' => $classroom->level,
            ])
            ->with('success', "Berhasil mengimpor {$updatedCount} nilai ekstrakurikuler dari file Excel.");
    }

    private function legacyIndex(Request $request, Period $period): View
    {
        $user = $request->user();
        $assignedClassroomIds = ExtracurricularRecord::query()
            ->where('period_id', $period->id)
            ->where('supervisor_id', $user->id)
            ->distinct()
            ->pluck('classroom_id');

        abort_if($assignedClassroomIds->isEmpty(), 403, 'Anda belum ditugaskan sebagai guru pembina.');

        $classrooms = Classroom::query()
            ->with('academicYear')
            ->where('academic_year_id', $period->academic_year_id)
            ->whereIn('id', $assignedClassroomIds)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $selectedClassroomId = $request->integer('classroom_id') ?: $classrooms->first()->id;
        abort_unless($assignedClassroomIds->contains($selectedClassroomId), 403, 'Anda bukan guru pembina pada kelas yang dipilih.');

        $classroom = $classrooms->firstWhere('id', $selectedClassroomId);
        $records = ExtracurricularRecord::query()
            ->with('student')
            ->where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->where('supervisor_id', $user->id)
            ->orderBy('student_id')
            ->orderBy('name')
            ->get()
            ->groupBy('student_id');

        return view('teacher.extracurricular', [
            'classroom' => $classroom,
            'classrooms' => $classrooms,
            'period' => $period,
            'records' => $records,
            'isLocked' => $classroom->isLockedInPeriod($period->id),
        ]);
    }

    private function legacyUpdate(ExtracurricularRequest $request): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');
        $classroom = Classroom::query()
            ->whereKey($request->validated('classroom_id'))
            ->where('academic_year_id', $period->academic_year_id)
            ->firstOrFail();

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Data ekstrakurikuler tidak dapat diubah lagi.');
        }

        $submitted = $request->validated('records', []);
        $userId = $request->user()->id;

        DB::transaction(function () use ($submitted, $period, $classroom, $userId): void {
            foreach ($submitted as $recordId => $record) {
                ExtracurricularRecord::query()
                    ->whereKey((int) $recordId)
                    ->where('period_id', $period->id)
                    ->where('classroom_id', $classroom->id)
                    ->where('supervisor_id', $userId)
                    ->update([
                        'predicate' => $record['predicate'],
                        'description' => filled($record['description'] ?? null)
                            ? trim($record['description'])
                            : null,
                    ]);
            }
        });

        return redirect()
            ->route('guru.ekstrakurikuler.index', ['classroom_id' => $classroom->id])
            ->with('success', 'Penilaian ekstrakurikuler berhasil disimpan.');
    }
}
