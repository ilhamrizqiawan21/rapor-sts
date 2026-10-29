<?php

namespace App\Http\Requests\Operator;

use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class AchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($user->isOperator()) {
            return true;
        }

        $period = app()->bound('activePeriod') ? app('activePeriod') : null;

        return $period
            && $user->isHomeroomTeacher((int) $this->input('classroom_id'), $period->academic_year_id);
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'achievements' => ['nullable', 'array'],
            'achievements.*' => ['array'],
            'achievements.*.*.name' => ['nullable', 'string', 'max:150'],
            'achievements.*.*.level' => ['nullable', 'required_with:achievements.*.*.name', 'in:Sekolah,Kecamatan,Kabupaten/Kota,Provinsi,Nasional,Internasional'],
            'achievements.*.*.rank' => ['nullable', 'string', 'max:50'],
            'achievements.*.*.achieved_at' => ['nullable', 'date'],
            'achievements.*.*.organizer' => ['nullable', 'string', 'max:150'],
            'achievements.*.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'achievements.*.*.name.max' => 'Nama prestasi maksimal 150 karakter.',
            'achievements.*.*.level.required_with' => 'Tingkat prestasi wajib dipilih jika nama prestasi diisi.',
            'achievements.*.*.level.in' => 'Tingkat prestasi tidak valid.',
            'achievements.*.*.rank.max' => 'Peringkat maksimal 50 karakter.',
            'achievements.*.*.achieved_at.date' => 'Tanggal prestasi tidak valid.',
            'achievements.*.*.organizer.max' => 'Penyelenggara maksimal 150 karakter.',
            'achievements.*.*.description.max' => 'Keterangan prestasi maksimal 255 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $period = app()->bound('activePeriod') ? app('activePeriod') : null;
            $classroom = Classroom::find((int) $this->input('classroom_id'));

            if (! $period || ! $classroom || $classroom->academic_year_id !== $period->academic_year_id) {
                $validator->errors()->add('classroom_id', 'Kelas tidak sesuai dengan periode aktif.');

                return;
            }

            $studentIds = array_map('intval', array_keys((array) $this->input('achievements', [])));
            $validStudentIds = Enrollment::query()
                ->where('classroom_id', $classroom->id)
                ->where('academic_year_id', $period->academic_year_id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->all();

            if (array_diff($studentIds, $validStudentIds)) {
                $validator->errors()->add('achievements', 'Terdapat siswa yang bukan anggota kelas yang dipilih.');
            }

            foreach ((array) $this->input('achievements', []) as $studentRecords) {
                $names = collect((array) $studentRecords)
                    ->map(fn ($record) => mb_strtolower(trim((string) ($record['name'] ?? ''))))
                    ->filter()
                    ->values();

                if ($names->count() !== $names->unique()->count()) {
                    $validator->errors()->add('achievements', 'Nama prestasi tidak boleh duplikat untuk siswa yang sama.');
                    break;
                }
            }
        });
    }
}
