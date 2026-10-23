<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Period;
use App\Models\TeachingAssignment;
use Illuminate\Support\Facades\DB;

class PeriodService
{
    public function setActivePeriod(Period $period): Period
    {
        return DB::transaction(function () use ($period) {
            Period::query()->update(['is_active' => false]);
            $period->update(['is_active' => true]);

            if (session()) {
                session(['period_id' => $period->id]);
            }

            ActivityLogger::log('activate_period', 'Mengaktifkan periode '.$period->badge_text, $period);

            return $period;
        });
    }

    public function copyTeachingAssignments(int $fromPeriodId, int $toPeriodId): int
    {
        return DB::transaction(function () use ($fromPeriodId, $toPeriodId) {
            $sourcePeriod = Period::findOrFail($fromPeriodId);
            $toPeriod = Period::findOrFail($toPeriodId);
            $sameYear = $sourcePeriod->academic_year_id === $toPeriod->academic_year_id;
            $targetClasses = $sameYear ? collect() : Classroom::where('academic_year_id', $toPeriod->academic_year_id)->get()->keyBy('name');

            $sourceAssignments = TeachingAssignment::with(['classroom', 'teacher', 'subject'])
                ->where('period_id', $fromPeriodId)
                ->whereHas('teacher', fn ($q) => $q->where('is_active', true))
                ->whereHas('subject', fn ($q) => $q->where('is_active', true))
                ->whereHas('classroom', fn ($q) => $q->where('academic_year_id', $sourcePeriod->academic_year_id))
                ->get();

            $copiedCount = 0;

            foreach ($sourceAssignments as $assignment) {
                $classroom = $sameYear ? $assignment->classroom : $targetClasses->get($assignment->classroom->name);
                if (! $classroom || $classroom->level !== $assignment->classroom->level || $classroom->curriculum !== $assignment->classroom->curriculum || $assignment->subject->curriculum !== $classroom->curriculum) {
                    continue;
                }

                // Jangan menimpa jika sudah ada di periode tujuan
                $exists = TeachingAssignment::where('period_id', $toPeriodId)
                    ->where('classroom_id', $classroom->id)
                    ->where('subject_id', $assignment->subject_id)
                    ->exists();

                if (! $exists) {
                    TeachingAssignment::create([
                        'period_id' => $toPeriodId,
                        'classroom_id' => $classroom->id,
                        'subject_id' => $assignment->subject_id,
                        'teacher_id' => $assignment->teacher_id,
                        'hours_per_week' => $assignment->hours_per_week,
                        'weight_summative' => $assignment->weight_summative,
                        'weight_sts' => $assignment->weight_sts,
                        'kktp' => $assignment->kktp,
                        'submitted_at' => null,
                        'submitted_by' => null,
                    ]);
                    $copiedCount++;
                }
            }

            ActivityLogger::log('copy_teaching_assignment', "Menyalin {$copiedCount} pembagian mengajar ke periode ".($toPeriod?->badge_text ?? $toPeriodId));

            return $copiedCount;
        });
    }
}
