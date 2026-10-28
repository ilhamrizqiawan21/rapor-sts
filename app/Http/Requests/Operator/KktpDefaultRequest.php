<?php

namespace App\Http\Requests\Operator;

use App\Models\MadrasahProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KktpDefaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'level' => ['required', 'integer', Rule::in(MadrasahProfile::first()?->classLevels() ?? range(1, 12))],
            'kktp' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
