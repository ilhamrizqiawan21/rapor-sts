<?php

namespace App\Http\Requests\Teacher;

use App\Http\Requests\Concerns\ValidatesEnrolledStudents;
use Illuminate\Foundation\Http\FormRequest;

class StsScoreRequest extends FormRequest
{
    use ValidatesEnrolledStudents;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('teachingAssignment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $validator->errors()->any()) {
                $this->ensureStudentsInAssignmentClass($validator, array_keys((array) $this->input('scores', [])));
            }
        });
    }
}
