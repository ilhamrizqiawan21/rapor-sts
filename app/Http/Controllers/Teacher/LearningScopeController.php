<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\LearningScopeRequest;
use App\Models\LearningScope;
use App\Models\SummativeScore;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use App\Services\Grading\AchievementResolver;
use App\Services\Grading\GradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LearningScopeController extends Controller
{
    public function index(TeachingAssignment $teachingAssignment, GradeCalculator $calculator, AchievementResolver $resolver): View
    {
        $this->authorize('view', $teachingAssignment);

        $teachingAssignment->loadMissing([
            'classroom.enrollments' => fn ($q) => $q->where('academic_year_id', $teachingAssignment->period->academic_year_id)->with('student'),
            'learningScopes.scores',
            'subject',
        ]);

        $students = $teachingAssignment->classroom->enrollments
            ->pluck('student')
            ->filter(fn ($s) => $s !== null && $s->is_active)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $scopes = $teachingAssignment->learningScopes()->orderBy('sequence')->get();

        // Siapkan struktur matriks nilai: [student_id => [scope_id => score]]
        $matrix = [];
        $averages = [];

        foreach ($students as $student) {
            $studentScores = [];
            foreach ($scopes as $scope) {
                $scoreModel = $scope->scores->firstWhere('student_id', $student->id);
                $studentScores[$scope->id] = $scoreModel?->score !== null ? (float) $scoreModel->score : null;
            }
            $matrix[$student->id] = $studentScores;
            $rawAvg = $calculator->calculateSummativeAverage($studentScores);
            $averages[$student->id] = $calculator->roundHalfUp($rawAvg);
        }

        $nextSequence = ($scopes->max('sequence') ?? 0) + 1;
        $defaultKktp = (float) $teachingAssignment->kktp;
        $intervals = $resolver->getIntervals($defaultKktp);

        return view('guru.sumatif', compact(
            'teachingAssignment',
            'students',
            'scopes',
            'matrix',
            'averages',
            'nextSequence',
            'defaultKktp',
            'intervals'
        ));
    }

    public function store(LearningScopeRequest $request, TeachingAssignment $teachingAssignment): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $teachingAssignment) {
            $scope = LearningScope::create([
                'teaching_assignment_id' => $teachingAssignment->id,
                'sequence' => $validated['sequence'],
                'summary' => $validated['summary'],
                'kktp' => $validated['kktp'],
            ]);

            if (! empty($validated['scores'])) {
                foreach ($validated['scores'] as $studentId => $score) {
                    if ($score !== null && $score !== '') {
                        SummativeScore::create([
                            'learning_scope_id' => $scope->id,
                            'student_id' => $studentId,
                            'score' => (float) $score,
                        ]);
                    }
                }
            }
        });

        ActivityLogger::log(
            'create_learning_scope',
            "Menambahkan LM {$validated['sequence']} ({$validated['summary']}) mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name}",
            $teachingAssignment
        );

        return redirect()->route('guru.sumatif.index', $teachingAssignment)
            ->with('success', "Lingkup materi LM {$validated['sequence']} beserta nilai siswa berhasil disimpan.");
    }

    public function update(LearningScopeRequest $request, TeachingAssignment $teachingAssignment, LearningScope $learningScope): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        if ($learningScope->teaching_assignment_id !== $teachingAssignment->id) {
            abort(404);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $learningScope) {
            $learningScope->update([
                'sequence' => $validated['sequence'],
                'summary' => $validated['summary'],
                'kktp' => $validated['kktp'],
            ]);

            if (isset($validated['scores'])) {
                foreach ($validated['scores'] as $studentId => $score) {
                    $scoreVal = ($score !== null && $score !== '') ? (float) $score : null;

                    SummativeScore::updateOrCreate(
                        [
                            'learning_scope_id' => $learningScope->id,
                            'student_id' => $studentId,
                        ],
                        [
                            'score' => $scoreVal,
                        ]
                    );
                }
            }
        });

        ActivityLogger::log(
            'update_learning_scope',
            "Memperbarui LM {$validated['sequence']} mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name}",
            $learningScope
        );

        return redirect()->route('guru.sumatif.index', $teachingAssignment)
            ->with('success', "Data LM {$validated['sequence']} berhasil diperbarui.");
    }

    public function destroy(TeachingAssignment $teachingAssignment, LearningScope $learningScope): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        if ($learningScope->teaching_assignment_id !== $teachingAssignment->id) {
            abort(404);
        }

        $seq = $learningScope->sequence;
        $scoresCount = $learningScope->scores()->count();

        DB::transaction(function () use ($learningScope) {
            $learningScope->scores()->delete();
            $learningScope->delete();
        });

        ActivityLogger::log(
            'delete_learning_scope',
            "Menghapus LM {$seq} beserta {$scoresCount} nilai siswa pada mapel {$teachingAssignment->subject->name} kelas {$teachingAssignment->classroom->name}",
            $teachingAssignment
        );

        return redirect()->route('guru.sumatif.index', $teachingAssignment)
            ->with('success', "Lingkup Materi LM {$seq} beserta riwayat nilainya berhasil dihapus.");
    }
}
