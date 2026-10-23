<?php

namespace App\Http\Requests\Homeroom;

use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class HomeroomRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array'],
            'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'records.*.sick' => ['nullable', 'integer', 'min:0'],
            'records.*.permit' => ['nullable', 'integer', 'min:0'],
            'records.*.absent' => ['nullable', 'integer', 'min:0'],
            'records.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'records.*.sick.integer' => 'Jumlah sakit harus berupa bilangan bulat.',
            'records.*.sick.min' => 'Jumlah sakit minimal 0.',
            'records.*.permit.integer' => 'Jumlah izin harus berupa bilangan bulat.',
            'records.*.permit.min' => 'Jumlah izin minimal 0.',
            'records.*.absent.integer' => 'Jumlah alpa harus berupa bilangan bulat.',
            'records.*.absent.min' => 'Jumlah alpa minimal 0.',
            'records.*.note.max' => 'Catatan wali kelas maksimal 500 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $period = app()->bound('activePeriod') ? app('activePeriod') : null;
            $classroom = $period ? Classroom::where('academic_year_id', $period->academic_year_id)
                ->where('homeroom_teacher_id', $this->user()?->id)->first() : null;
            if (! $classroom) {
                return;
            }

            $ids = array_map('intval', array_column((array) $this->input('records', []), 'student_id'));
            $valid = Enrollment::where('classroom_id', $classroom->id)
                ->where('academic_year_id', $period->academic_year_id)
                ->whereIn('student_id', $ids)->pluck('student_id')->all();

            if (array_diff($ids, $valid)) {
                $validator->errors()->add('records', 'Terdapat siswa yang bukan anggota kelas Anda.');
            }
        });
    }
}
