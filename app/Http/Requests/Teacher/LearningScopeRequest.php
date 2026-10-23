<?php

namespace App\Http\Requests\Teacher;

use App\Http\Requests\Concerns\ValidatesEnrolledStudents;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningScopeRequest extends FormRequest
{
    use ValidatesEnrolledStudents;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('teachingAssignment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'sequence' => [
                'required', 'integer', 'min:1', 'max:255',
                Rule::unique('learning_scopes', 'sequence')
                    ->where('teaching_assignment_id', $this->route('teachingAssignment')?->id)
                    ->ignore($this->route('learningScope')?->id),
            ],
            'summary' => ['required', 'string', 'max:500'],
            'kktp' => ['required', 'numeric', 'between:0,100'],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sequence.unique' => 'Nomor LM tersebut sudah dipakai pada mapel ini.',
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
