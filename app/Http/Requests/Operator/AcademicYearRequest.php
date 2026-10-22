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
                'regex:/^\d{4}\/\d{4}$/',
                function (string $attribute, mixed $value, \Closure $fail) {
                    [$from, $to] = array_map('intval', explode('/', (string) $value) + [1 => 0]);
                    if ($to !== $from + 1) {
                        $fail('Tahun kedua harus satu tahun setelah tahun pertama (contoh: 2026/2027).');
                    }
                },
                Rule::unique('academic_years', 'name')->ignore($id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tahun pelajaran wajib diisi.',
            'name.regex' => 'Format tahun pelajaran harus YYYY/YYYY (contoh: 2026/2027).',
            'name.unique' => 'Tahun pelajaran sudah ada.',
        ];
    }
}
