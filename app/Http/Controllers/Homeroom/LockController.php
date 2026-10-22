<?php

namespace App\Http\Controllers\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomLock;
use App\Models\Period;
use App\Models\TeachingAssignment;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;

class LockController extends Controller
{
    public function lock(Classroom $classroom): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('lock', $classroom);

        if ($classroom->isLockedInPeriod($period->id)) {
            return back()->with('info', "Nilai kelas {$classroom->name} sudah dalam keadaan terkunci.");
        }

        // Cek syarat kunci: Semua penugasan mengajar di kelas ini pada periode aktif harus sudah terkirim
        $assignments = TeachingAssignment::where('classroom_id', $classroom->id)
            ->where('period_id', $period->id)
            ->get();

        if ($assignments->isEmpty()) {
            return back()->with('error', "Tidak dapat mengunci nilai karena belum ada pembagian mengajar di kelas {$classroom->name}.");
        }

        $unsubmitted = $assignments->filter(fn ($a) => ! $a->isSubmitted());

        if ($unsubmitted->isNotEmpty()) {
            return back()->with('error', 'Tidak dapat mengunci nilai kelas karena masih ada mata pelajaran yang belum terkirim.');
        }

        ClassroomLock::firstOrCreate(
            [
                'period_id' => $period->id,
                'classroom_id' => $classroom->id,
            ],
            [
                'locked_by' => auth()->id(),
                'locked_at' => now(),
            ]
        );

        ActivityLogger::log('lock_class', "Mengunci nilai kelas {$classroom->name}", $classroom);

        return back()->with('success', "Nilai rapor kelas {$classroom->name} berhasil dikunci.");
    }

    public function unlock(Classroom $classroom): RedirectResponse
    {
        /** @var Period $period */
        $period = app('activePeriod');

        $this->authorize('unlock', $classroom);

        $lock = ClassroomLock::where('period_id', $period->id)
            ->where('classroom_id', $classroom->id)
            ->first();

        if ($lock) {
            $lock->delete();
            ActivityLogger::log('unlock_class', "Membuka kunci nilai kelas {$classroom->name}", $classroom);

            return back()->with('success', "Kunci nilai kelas {$classroom->name} berhasil dibuka.");
        }

        return back()->with('info', "Kelas {$classroom->name} tidak sedang dikunci.");
    }
}
