<?php

namespace App\Http\Requests\Concerns;

use App\Models\Enrollment;
use Illuminate\Validation\Validator;

trait ValidatesEnrolledStudents
{
    /**
     * Pastikan semua ID siswa (kunci array) terdaftar di kelas penugasan pada tahun pelajarannya.
     *
     * @param  list<int|string>  $studentIds
     */
    protected function ensureStudentsInAssignmentClass(Validator $validator, array $studentIds, string $field = 'scores'): void
    {
        $assignment = $this->route('teachingAssignment');
        if (! $assignment || $studentIds === []) {
            return;
        }

        $studentIds = array_map('intval', $studentIds);
        $valid = Enrollment::where('classroom_id', $assignment->classroom_id)
            ->where('academic_year_id', $assignment->period->academic_year_id)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        if (array_diff($studentIds, $valid)) {
            $validator->errors()->add($field, 'Terdapat siswa yang bukan anggota kelas ini.');
        }
    }
}
