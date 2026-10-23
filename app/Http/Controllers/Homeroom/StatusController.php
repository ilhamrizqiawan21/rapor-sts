<?php

namespace App\Http\Controllers\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Period;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StatusController extends Controller
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

        $statusData = $this->computeClassStatus($classroom, $period);

        return view('homeroom.status', array_merge($statusData, [
            'classroom' => $classroom,
            'period' => $period,
            'isHomeroomView' => true,
        ]));
    }

    public function unsubmit(TeachingAssignment $teachingAssignment): RedirectResponse
    {
        $classroom = $teachingAssignment->classroom;
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('unsubmit', [$classroom, $period]);

        if ($teachingAssignment->period_id !== $period->id) {
            return back()->with('error', 'Penugasan ini bukan milik periode aktif.');
        }

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('error', 'Tidak dapat membatalkan kirim nilai karena kelas sedang dikunci.');
        }

        if (! $teachingAssignment->isSubmitted()) {
            return back()->with('error', 'Nilai mapel ini belum dikirim.');
        }

        $teachingAssignment->update([
            'submitted_at' => null,
            'submitted_by' => null,
        ]);

        ActivityLogger::log(
            'unsubmit_grade',
            "Membatalkan kirim nilai {$teachingAssignment->subject->name} kelas {$classroom->name}",
            $teachingAssignment
        );

        return back()->with('success', "Kirim nilai {$teachingAssignment->subject->name} berhasil dibatalkan. Guru dapat kembali mengedit nilai.");
    }

    /**
     * Hitung progres nilai dan susun tabel status mata pelajaran untuk suatu kelas.
     *
     * @return array<string, mixed>
     */
    public function computeClassStatus(Classroom $classroom, Period $period): array
    {
        $classroom->loadMissing([
            'enrollments' => fn ($q) => $q->where('academic_year_id', $period->academic_year_id)->with('student'),
        ]);

        $enrolledStudents = $classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active);

        $studentCount = $enrolledStudents->count();

        $assignments = TeachingAssignment::with([
            'subject.parent',
            'teacher',
            'learningScopes.scores',
            'stsScores',
        ])
            ->where('classroom_id', $classroom->id)
            ->where('period_id', $period->id)
            ->get();

        $totalAssignments = $assignments->count();
        $completeLmCount = 0;
        $stsEligibleCount = 0;
        $completeStsCount = 0;
        $allSubmitted = ($totalAssignments > 0);

        foreach ($assignments as $a) {
            if (! $a->isSubmitted()) {
                $allSubmitted = false;
            }

            // Hitung LM complete: punya >= 1 LM dan semua siswa punya >= 1 nilai LM
            $lmCount = $a->learningScopes->count();
            if ($lmCount > 0 && $studentCount > 0) {
                $studentsWithAtLeastOneScore = $a->learningScopes
                    ->flatMap(fn ($ls) => $ls->scores->pluck('student_id'))
                    ->unique()
                    ->count();

                if ($studentsWithAtLeastOneScore >= $studentCount) {
                    $completeLmCount++;
                }
            }

            // Hitung STS complete: jika wT > 0, semua siswa punya nilai STS
            if ((float) $a->weight_sts > 0) {
                $stsEligibleCount++;
                if ($studentCount > 0) {
                    $stsWithScoreCount = $a->stsScores
                        ->filter(fn ($s) => $s->score !== null)
                        ->count();

                    if ($stsWithScoreCount >= $studentCount) {
                        $completeStsCount++;
                    }
                }
            }
        }

        $progressLm = $totalAssignments > 0 ? (int) round(($completeLmCount / $totalAssignments) * 100) : 0;
        $progressSts = $stsEligibleCount > 0 ? (int) round(($completeStsCount / $stsEligibleCount) * 100) : 100;

        // Susun baris tabel sesuai urutan PAI parent & sub-mapel, lalu mapel mandiri
        $tableRows = $this->formatTableRows($assignments, $studentCount);

        $isLocked = $classroom->isLockedInPeriod($period->id);

        return [
            'progressLm' => $progressLm,
            'progressSts' => $progressSts,
            'totalAssignments' => $totalAssignments,
            'allSubmitted' => $allSubmitted,
            'isLocked' => $isLocked,
            'tableRows' => $tableRows,
        ];
    }

    /**
     * @param  Collection<int, TeachingAssignment>  $assignments
     * @return array<int, array>
     */
    protected function formatTableRows($assignments, int $studentCount): array
    {
        $parentGroups = [];
        $standalone = [];

        foreach ($assignments as $a) {
            $subj = $a->subject;
            if ($subj->parent_id && $subj->parent) {
                $parent = $subj->parent;
                if (! isset($parentGroups[$parent->id])) {
                    $parentGroups[$parent->id] = [
                        'is_parent' => true,
                        'name' => $parent->name,
                        'sort_order' => $parent->sort_order,
                        'assignments' => [],
                    ];
                }
                $parentGroups[$parent->id]['assignments'][] = $a;
            } else {
                $standalone[] = [
                    'is_parent' => false,
                    'assignment' => $a,
                    'sort_order' => $subj->sort_order,
                ];
            }
        }

        foreach ($parentGroups as &$pg) {
            usort($pg['assignments'], fn ($x, $y) => $x->subject->sort_order <=> $y->subject->sort_order);
        }
        unset($pg);

        $merged = array_merge(array_values($parentGroups), $standalone);
        usort($merged, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);

        $rows = [];
        $no = 1;

        foreach ($merged as $item) {
            if ($item['is_parent']) {
                $rows[] = [
                    'is_header' => true,
                    'no' => (string) $no++,
                    'subject_name' => $item['name'],
                    'teacher_name' => '',
                    'lm_text' => '',
                    'sts_text' => '',
                    'is_submitted' => false,
                    'assignment' => null,
                ];

                $childLetterIdx = 0;
                foreach ($item['assignments'] as $a) {
                    $letter = chr(65 + $childLetterIdx++).'.';
                    $lmCount = $a->learningScopes->count();

                    $stsStatus = '–';
                    if ((float) $a->weight_sts > 0) {
                        $stsFilled = $a->stsScores->filter(fn ($s) => $s->score !== null)->count();
                        $stsStatus = ($studentCount > 0 && $stsFilled >= $studentCount) ? 'Terisi' : 'Belum';
                    }

                    $rows[] = [
                        'is_header' => false,
                        'no' => '',
                        'letter' => $letter,
                        'subject_name' => $a->subject->name.' ('.$a->subject->code.')',
                        'teacher_name' => $a->teacher?->name ?? '–',
                        'lm_text' => $lmCount.' LM',
                        'sts_text' => $stsStatus,
                        'is_submitted' => $a->isSubmitted(),
                        'assignment' => $a,
                    ];
                }
            } else {
                $a = $item['assignment'];
                $lmCount = $a->learningScopes->count();

                $stsStatus = '–';
                if ((float) $a->weight_sts > 0) {
                    $stsFilled = $a->stsScores->filter(fn ($s) => $s->score !== null)->count();
                    $stsStatus = ($studentCount > 0 && $stsFilled >= $studentCount) ? 'Terisi' : 'Belum';
                }

                $rows[] = [
                    'is_header' => false,
                    'no' => (string) $no++,
                    'letter' => '',
                    'subject_name' => $a->subject->name.' ('.$a->subject->code.')',
                    'teacher_name' => $a->teacher?->name ?? '–',
                    'lm_text' => $lmCount.' LM',
                    'sts_text' => $stsStatus,
                    'is_submitted' => $a->isSubmitted(),
                    'assignment' => $a,
                ];
            }
        }

        return $rows;
    }
}
