<?php

namespace App\Http\Requests\Homeroom;

use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class ExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'extracurriculars' => ['nullable', 'array'],
            'extracurriculars.*' => ['array'],
            'extracurriculars.*.*.name' => ['nullable', 'string', 'max:100'],
            'extracurriculars.*.*.predicate' => ['nullable', 'required_with:extracurriculars.*.*.name', 'in:Sangat Baik,Baik,Cukup,Kurang'],
            'extracurriculars.*.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'extracurriculars.*.*.name.max' => 'Nama ekstrakurikuler maksimal 100 karakter.',
            'extracurriculars.*.*.predicate.required_with' => 'Predikat wajib dipilih jika nama ekstrakurikuler diisi.',
            'extracurriculars.*.*.predicate.in' => 'Predikat ekstrakurikuler tidak valid.',
            'extracurriculars.*.*.description.max' => 'Keterangan ekstrakurikuler maksimal 255 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $period = app()->bound('activePeriod') ? app('activePeriod') : null;
            $classroom = $period
                ? Classroom::where('academic_year_id', $period->academic_year_id)
                    ->where('homeroom_teacher_id', $this->user()?->id)
                    ->first()
                : null;

            if (! $classroom) {
                return;
            }

            $studentIds = array_map('intval', array_keys((array) $this->input('extracurriculars', [])));
            $validStudentIds = Enrollment::query()
                ->where('classroom_id', $classroom->id)
                ->where('academic_year_id', $period->academic_year_id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->all();

            if (array_diff($studentIds, $validStudentIds)) {
                $validator->errors()->add('extracurriculars', 'Terdapat siswa yang bukan anggota kelas Anda.');
            }

            foreach ((array) $this->input('extracurriculars', []) as $studentRecords) {
                $names = collect((array) $studentRecords)
                    ->map(fn ($record) => mb_strtolower(trim((string) ($record['name'] ?? ''))))
                    ->filter()
                    ->values();

                if ($names->count() !== $names->unique()->count()) {
                    $validator->errors()->add('extracurriculars', 'Nama ekstrakurikuler tidak boleh duplikat untuk siswa yang sama.');
                    break;
                }
            }
        });
    }
}
