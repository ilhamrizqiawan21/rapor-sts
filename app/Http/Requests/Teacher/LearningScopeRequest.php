<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class LearningScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('teachingAssignment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'sequence' => ['required', 'integer', 'min:1'],
            'summary' => ['required', 'string', 'max:500'],
            'kktp' => ['required', 'numeric', 'between:0,100'],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
