<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class WeightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('teachingAssignment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'hours_per_week' => ['required', 'integer', 'min:0'],
            'weight_summative' => ['required', 'numeric', 'min:0'],
            'weight_sts' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $wS = (float) $this->input('weight_summative', 0);
            $wT = (float) $this->input('weight_sts', 0);

            if ($wS <= 0 && $wT <= 0) {
                $validator->errors()->add('weight_summative', 'Bobot sumatif dan STS tidak boleh keduanya bernilai 0.');
            }
        });
    }
}
