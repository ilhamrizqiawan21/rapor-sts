<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('period')?->id ?? $this->period;

        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester' => [
                'required',
                'in:1,2',
                Rule::unique('periods', 'semester')
                    ->where('academic_year_id', $this->academic_year_id)
                    ->ignore($id),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
