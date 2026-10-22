<?php

namespace App\Http\Requests\Teacher;

use App\Models\TeachingAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RdmTemplateFillRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TeachingAssignment|null $assignment */
        $assignment = $this->route('teachingAssignment');

        return $assignment !== null && $this->user()->can('view', $assignment);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_file.required' => 'Berkas template RDM (.xlsx) wajib dipilih.',
            'template_file.file' => 'Berkas yang diunggah tidak valid.',
            'template_file.mimes' => 'Format berkas harus berekstensi .xlsx.',
            'template_file.max' => 'Ukuran berkas maksimal adalah 10 MB.',
        ];
    }
}
