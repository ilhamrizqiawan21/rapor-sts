<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class CompetencyOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('teachingAssignment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:300'],
        ];
    }
}
