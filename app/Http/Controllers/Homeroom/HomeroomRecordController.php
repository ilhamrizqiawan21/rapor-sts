<?php

namespace App\Http\Controllers\Homeroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Homeroom\HomeroomBatchNoteRequest;
use App\Http\Requests\Homeroom\HomeroomImportRequest;
use App\Http\Requests\Homeroom\HomeroomRecordRequest;
use App\Models\Classroom;
use App\Models\HomeroomRecord;
use App\Models\Period;
use App\Services\ActivityLogger;
use App\Services\Import\HomeroomImporter;
use App\Services\Import\TemplateBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeroomRecordController extends Controller
{
    protected function getHomeroomClassroom(): Classroom
    {
        $user = auth()->user();
        /** @var Period|null $period */
        $period = app()->bound('activePeriod') ? app('activePeriod') : null;

        if (! $user || ! $period) {
            abort(403, 'Periode aktif tidak ditemukan.');
        }

        $classroom = Classroom::where('academic_year_id', $period->academic_year_id)
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

        $this->authorize('viewHomeroom', $classroom);

        $classroom->loadMissing([
            'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
        ]);

        $students = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $records = HomeroomRecord::where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->get()
            ->keyBy('student_id');

        $isLocked = $classroom->isLockedInPeriod($period->id);

        return view('homeroom.records', compact('classroom', 'period', 'students', 'records', 'isLocked'));
    }

    public function update(HomeroomRecordRequest $request): RedirectResponse
    {
        $classroom = $this->getHomeroomClassroom();
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('updateHomeroom', $classroom);

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Perubahan kehadiran & catatan tidak diizinkan.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $classroom, $period) {
            foreach ($validated['records'] as $record) {
                HomeroomRecord::updateOrCreate(
                    [
                        'period_id' => $period->id,
                        'student_id' => $record['student_id'],
                    ],
                    [
                        'classroom_id' => $classroom->id,
                        'sick' => $record['sick'] ?? 0,
                        'permit' => $record['permit'] ?? 0,
                        'absent' => $record['absent'] ?? 0,
                        'note' => $record['note'] ?? null,
                    ]
                );
            }

            ActivityLogger::log('save_homeroom', "Menyimpan kehadiran & catatan kelas {$classroom->name}", $classroom);
        });

        return back()->with('success', 'Kehadiran dan catatan wali kelas berhasil disimpan.');
    }

    public function batchNote(HomeroomBatchNoteRequest $request): RedirectResponse
    {
        $classroom = $this->getHomeroomClassroom();
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('updateHomeroom', $classroom);

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Kelas telah dikunci. Perubahan catatan tidak diizinkan.');
        }

        $note = $request->validated('note');

        $classroom->loadMissing([
            'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
        ]);

        $studentIds = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->pluck('id');

        $existingRecords = HomeroomRecord::where('period_id', $period->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $updatedCount = 0;

        DB::transaction(function () use ($studentIds, $existingRecords, $note, $period, $classroom, &$updatedCount) {
            foreach ($studentIds as $studentId) {
                $record = $existingRecords->get($studentId);

                if (! $record) {
                    HomeroomRecord::create([
                        'period_id' => $period->id,
                        'classroom_id' => $classroom->id,
                        'student_id' => $studentId,
                        'sick' => 0,
                        'permit' => 0,
                        'absent' => 0,
                        'note' => $note,
                    ]);
                    $updatedCount++;
                } elseif (empty(trim((string) $record->note))) {
                    $record->update(['note' => $note]);
                    $updatedCount++;
                }
            }

            ActivityLogger::log('batch_note_homeroom', "Isi catatan massal untuk {$updatedCount} siswa kelas {$classroom->name}", $classroom);
        });

        return back()->with('success', "Catatan massal berhasil diisi untuk {$updatedCount} siswa yang catatannya masih kosong.");
    }

    public function template(TemplateBuilder $builder): StreamedResponse
    {
        $classroom = $this->getHomeroomClassroom();
        /** @var Period $period */
        $period = app('activePeriod');

        $spreadsheet = $builder->buildHomeroomTemplate($classroom, $period);
        $filename = 'Template_Kehadiran_Catatan_'.$classroom->name.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(HomeroomImportRequest $request, HomeroomImporter $importer): RedirectResponse
    {
        $classroom = $this->getHomeroomClassroom();
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('updateHomeroom', $classroom);

        $file = $request->file('file');
        $result = $importer->import($file, $classroom, $period);

        if (! $result['success']) {
            return back()->withErrors(['file' => $result['errors']]);
        }

        $msg = "Berhasil mengimpor {$result['imported']} data kehadiran & catatan.";
        if (! empty($result['errors'])) {
            $msg .= ' Peringatan: '.implode(' ', $result['errors']);
        }

        return back()->with('success', $msg);
    }
}
