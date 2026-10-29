<?php

namespace App\Http\Requests\Operator;

use App\Models\MadrasahProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'level' => ['required', 'integer', Rule::in(MadrasahProfile::first()?->classLevels() ?? range(1, 12))],
            'curriculum' => ['required', 'in:merdeka,k13'],
            'phase' => ['nullable', 'string', 'max:5'],
            'prefix' => ['required', 'string', 'max:20'], // contoh "VII."
            'parallels' => ['required', 'string'], // contoh "A, B, C, D"
        ];
    }

    protected function prepareForValidation(): void
    {
        $level = trim((string) $this->input('level'));
        $romanLevels = collect(range(1, 12))
            ->mapWithKeys(fn (int $classLevel): array => [MadrasahProfile::classLevelRoman($classLevel) => $classLevel])
            ->all();

        if ($level !== '' && isset($romanLevels[strtoupper($level)])) {
            $this->merge(['level' => $romanLevels[strtoupper($level)]]);
        }
    }

    public function messages(): array
    {
        return [
            'level.in' => 'Tingkat kelas harus sesuai jenjang madrasah yang dipilih.',
            'level.integer' => 'Tingkat kelas harus berupa angka Romawi yang valid.',
        ];
    }
}
