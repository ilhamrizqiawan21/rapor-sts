<?php

namespace App\Http\Requests\Operator;

use App\Enums\Role;
use App\Models\MadrasahProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('classroom')?->id ?? $this->classroom;

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'level' => ['required', 'integer', Rule::in(MadrasahProfile::first()?->classLevels() ?? range(1, 12))],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms', 'name')
                    ->where('academic_year_id', $this->academic_year_id)
                    ->ignore($id),
            ],
            'alias' => ['nullable', 'string', 'max:50'],
            'curriculum' => ['required', 'in:merdeka,k13'],
            'phase' => ['nullable', 'string', 'max:5'],
            'homeroom_teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', Role::Guru->value)->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'level.in' => 'Tingkat kelas harus sesuai jenjang madrasah yang dipilih.',
        ];
    }
}
