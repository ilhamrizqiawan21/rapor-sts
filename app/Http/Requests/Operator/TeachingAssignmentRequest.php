<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class TeachingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'exists:periods,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', 'exists:users,id'],
            'hours_per_week' => ['nullable', 'integer', 'min:0'],
            'kktp' => ['nullable', 'numeric', 'between:0,100'],
            'weight_summative' => ['nullable', 'numeric', 'min:0'],
            'weight_sts' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
