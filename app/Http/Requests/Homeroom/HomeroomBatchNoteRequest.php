<?php

namespace App\Http\Requests\Homeroom;

use Illuminate\Foundation\Http\FormRequest;

class HomeroomBatchNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Catatan massal wajib diisi.',
            'note.max' => 'Catatan massal maksimal 500 karakter.',
        ];
    }
}
