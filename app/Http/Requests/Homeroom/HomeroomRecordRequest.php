<?php

namespace App\Http\Requests\Homeroom;

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
}
