<?php

namespace App\Http\Requests\Operator;

use App\Enums\Role;
use App\Models\ExtracurricularActivityDefinition;
use App\Models\Period;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtracurricularActivityDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'activities' => ['nullable', 'array'],
            'activities.*' => ['array'],
            'activities.*.id' => ['nullable', 'integer', 'exists:extracurricular_activity_definitions,id'],
            'activities.*.name' => ['required', 'string', 'max:100'],
            'activities.*.supervisor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', Role::Guru->value)
                    ->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'activities.*.name.required' => 'Nama kegiatan ekstrakurikuler wajib diisi.',
            'activities.*.name.max' => 'Nama kegiatan ekstrakurikuler maksimal 100 karakter.',
            'activities.*.supervisor_id.required' => 'Guru pembina wajib dipilih.',
            'activities.*.supervisor_id.exists' => 'Guru pembina harus merupakan guru aktif.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->any()) {
                return;
            }

            /** @var Period|null $period */
            $period = app()->bound('activePeriod') ? app('activePeriod') : null;
            if (! $period) {
                $validator->errors()->add('activities', 'Periode aktif tidak ditemukan.');

                return;
            }

            $activities = (array) $this->input('activities', []);
            $names = collect($activities)
                ->map(fn (array $activity): string => mb_strtolower(trim($activity['name'])))
                ->values();

            if ($names->count() !== $names->unique()->count()) {
                $validator->errors()->add('activities', 'Nama kegiatan tidak boleh duplikat dalam satu periode.');
            }

            $ids = collect($activities)
                ->pluck('id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->values();

            $validIds = ExtracurricularActivityDefinition::query()
                ->where('period_id', $period->id)
                ->whereIn('id', $ids)
                ->pluck('id');

            if ($ids->diff($validIds)->isNotEmpty()) {
                $validator->errors()->add('activities', 'Terdapat kegiatan yang bukan bagian dari periode aktif.');
            }
        });
    }
}
