<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('academic_year')?->id ?? $this->academic_year;

        return [
            'name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('academic_years', 'name')->ignore($id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tahun pelajaran wajib diisi.',
            'name.unique' => 'Tahun pelajaran sudah ada.',
        ];
    }
}
