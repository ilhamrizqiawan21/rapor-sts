<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class StsScoreRequest extends FormRequest
{
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
}
