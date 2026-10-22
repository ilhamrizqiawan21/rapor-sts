<?php

namespace App\Http\Requests\Homeroom;

use Illuminate\Foundation\Http\FormRequest;

class HomeroomImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'file.required' => 'File Excel wajib diunggah.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx).',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ];
    }
}
