<?php

namespace App\Policies;

use App\Models\TeachingAssignment;
use App\Models\User;

class TeachingAssignmentPolicy
{
    /**
     * Boleh melihat penilaian: Guru pengampu, Wali kelas dari kelas tersebut, Operator, atau Kepala Madrasah.
     */
    public function view(User $user, TeachingAssignment $assignment): bool
    {
        if ($user->isOperator() || $user->isKepala()) {
            return true;
        }

        if ($user->id === $assignment->teacher_id) {
            return true;
        }

        // Wali kelas dari kelas ini
        return $assignment->classroom->homeroom_teacher_id === $user->id;
    }

    /**
     * Boleh mengubah data penilaian (bobot, sumatif LM, STS, override):
     * - Hanya guru pengampu penugasan tersebut
     * - Belum dikirim oleh guru
     * - Kelas belum dikunci oleh wali kelas pada periode terkait
     */
    public function update(User $user, TeachingAssignment $assignment): bool
    {
        if ($user->id !== $assignment->teacher_id) {
            return false;
        }

        if ($assignment->isSubmitted()) {
            return false;
        }

        if ($assignment->classroom->isLockedInPeriod($assignment->period_id)) {
            return false;
        }

        return true;
    }

    /**
     * Boleh mengirim nilai (submit):
     * - Hanya guru pengampu penugasan tersebut
     * - Belum dikirim
     * - Kelas belum dikunci
     */
    public function submit(User $user, TeachingAssignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }
}
