<?php

namespace App\Http\Requests\Teacher;

use App\Models\Classroom;
use App\Models\ExtracurricularRecord;
use Illuminate\Foundation\Http\FormRequest;

class ExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isGuru() ?? false;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'records' => ['nullable', 'array'],
            'records.*.predicate' => ['required', 'in:Sangat Baik,Baik,Cukup,Kurang'],
            'records.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'records.*.predicate.required' => 'Predikat wajib dipilih.',
            'records.*.predicate.in' => 'Predikat ekstrakurikuler tidak valid.',
            'records.*.description.max' => 'Keterangan ekstrakurikuler maksimal 255 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->any()) {
                return;
            }

            $period = app()->bound('activePeriod') ? app('activePeriod') : null;
            $classroom = $period
                ? Classroom::query()
                    ->whereKey($this->integer('classroom_id'))
                    ->where('academic_year_id', $period->academic_year_id)
                    ->whereHas('extracurricularRecords', fn ($query) => $query
                        ->where('period_id', $period->id)
                        ->where('supervisor_id', $this->user()?->id))
                    ->first()
                : null;

            if (! $classroom) {
                $validator->errors()->add('classroom_id', 'Anda bukan guru pembina pada kelas yang dipilih.');

                return;
            }

            $recordIds = collect((array) $this->input('records', []))
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->values();

            $validRecordIds = ExtracurricularRecord::query()
                ->where('period_id', $period->id)
                ->where('classroom_id', $classroom->id)
                ->where('supervisor_id', $this->user()?->id)
                ->whereIn('id', $recordIds)
                ->pluck('id');

            if ($recordIds->diff($validRecordIds)->isNotEmpty()) {
                $validator->errors()->add('records', 'Terdapat data ekstrakurikuler yang bukan tanggung jawab Anda.');
            }
        });
    }
}
