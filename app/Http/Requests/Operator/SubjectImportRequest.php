<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class SubjectImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File Excel mapel wajib diunggah.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx).',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ];
    }
}
