<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class PrintSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'report_title' => ['required', 'string', 'max:255'],
            'print_place' => ['required', 'string', 'max:255'],
            'print_date' => ['required', 'date'],
            'score_label' => ['required', 'string', 'max:100'],
            'paper_size' => ['required', 'in:A4,F4'],
            'watermark_text' => ['nullable', 'string', 'max:255'],
            'sections' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'report_title.required' => 'Judul rapor wajib diisi.',
            'print_place.required' => 'Tempat cetak wajib diisi.',
            'print_date.required' => 'Tanggal cetak wajib diisi.',
            'score_label.required' => 'Label kolom nilai wajib diisi.',
            'paper_size.required' => 'Ukuran kertas wajib dipilih.',
            'paper_size.in' => 'Ukuran kertas harus A4 atau F4.',
        ];
    }
}
