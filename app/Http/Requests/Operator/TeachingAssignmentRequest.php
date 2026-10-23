<?php

namespace App\Http\Requests\Operator;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\Period;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeachingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'period_id' => ['required', 'exists:periods,id'],
            'classroom_id' => ['required', 'exists:classrooms,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'teacher_id' => ['required', Rule::exists('users', 'id')->where('role', Role::Guru->value)->where('is_active', true)],
            'hours_per_week' => ['nullable', 'integer', 'min:0'],
            'kktp' => ['nullable', 'numeric', 'between:0,100'],
            'weight_summative' => ['nullable', 'numeric', 'min:0'],
            'weight_sts' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $period = Period::find($this->input('period_id'));
            $classroom = Classroom::find($this->input('classroom_id'));
            if ($period && $classroom && $period->academic_year_id !== $classroom->academic_year_id) {
                $validator->errors()->add('classroom_id', 'Kelas bukan bagian dari tahun pelajaran periode ini.');
            }

            $wS = (float) $this->input('weight_summative', 50);
            $wT = (float) $this->input('weight_sts', 50);
            if ($wS <= 0 && $wT <= 0) {
                $validator->errors()->add('weight_summative', 'Bobot sumatif dan STS tidak boleh keduanya bernilai 0.');
            }
        });
    }
}
