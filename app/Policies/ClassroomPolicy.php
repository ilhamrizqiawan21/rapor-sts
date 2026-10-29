<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\Period;
use App\Models\User;

class ClassroomPolicy
{
    /**
     * Boleh melihat catatan & kehadiran wali kelas.
     */
    public function viewHomeroom(User $user, Classroom $classroom): bool
    {
        if ($user->isOperator() || $user->isKepala()) {
            return true;
        }

        return $classroom->homeroom_teacher_id === $user->id;
    }

    /**
     * Boleh mengubah data kehadiran & catatan wali kelas:
     * - Guru adalah wali kelas dari kelas ini (atau operator)
     * - Kelas belum dikunci pada periode aktif
     */
    public function updateHomeroom(User $user, Classroom $classroom, ?Period $period = null): bool
    {
        if (! $user->isOperator() && $classroom->homeroom_teacher_id !== $user->id) {
            return false;
        }

        $activePeriod = $period ?? (app()->bound('activePeriod') ? app('activePeriod') : null);

        if ($activePeriod && $classroom->isLockedInPeriod($activePeriod->id)) {
            return false;
        }

        return true;
    }

    /**
     * Boleh melihat data prestasi siswa pada kelas ini:
     * - Operator, atau wali kelas dari kelas ini.
     */
    public function viewAchievements(User $user, Classroom $classroom): bool
    {
        if ($user->isOperator()) {
            return true;
        }

        return $user->isGuru() && $classroom->homeroom_teacher_id === $user->id;
    }

    /**
     * Boleh mengubah data prestasi siswa pada kelas ini:
     * - Operator, atau wali kelas dari kelas ini
     * - Kelas belum dikunci pada periode aktif
     */
    public function updateAchievements(User $user, Classroom $classroom, ?Period $period = null): bool
    {
        if (! $user->isOperator() && (! $user->isGuru() || $classroom->homeroom_teacher_id !== $user->id)) {
            return false;
        }

        $activePeriod = $period ?? (app()->bound('activePeriod') ? app('activePeriod') : null);

        return ! $activePeriod || ! $classroom->isLockedInPeriod($activePeriod->id);
    }

    /**
     * Boleh mengunci nilai kelas:
     * - Hanya wali kelas dari kelas ini atau operator
     */
    public function lock(User $user, Classroom $classroom): bool
    {
        if ($user->isOperator()) {
            return true;
        }

        return $classroom->homeroom_teacher_id === $user->id;
    }

    /**
     * Boleh membuka kunci nilai kelas:
     * - Hanya wali kelas dari kelas ini atau operator
     */
    public function unlock(User $user, Classroom $classroom): bool
    {
        return $this->lock($user, $classroom);
    }

    /**
     * Boleh membatalkan kirim nilai guru:
     * - Wali kelas dari kelas ini atau operator
     * - Kelas belum dikunci
     */
    public function unsubmit(User $user, Classroom $classroom, ?Period $period = null): bool
    {
        if (! $user->isOperator() && $classroom->homeroom_teacher_id !== $user->id) {
            return false;
        }

        $activePeriod = $period ?? (app()->bound('activePeriod') ? app('activePeriod') : null);

        if ($activePeriod && $classroom->isLockedInPeriod($activePeriod->id)) {
            return false;
        }

        return true;
    }

    /**
     * Boleh mencetak rapor dan leger:
     * - Wali kelas dari kelas ini, operator, atau kepala madrasah
     */
    public function print(User $user, Classroom $classroom): bool
    {
        if ($user->isOperator() || $user->isKepala()) {
            return true;
        }

        return $classroom->homeroom_teacher_id === $user->id;
    }
}
