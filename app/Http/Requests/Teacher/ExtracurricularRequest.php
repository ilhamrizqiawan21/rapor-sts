<?php

namespace App\Http\Requests\Teacher;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\ExtracurricularActivityDefinition;
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
            'activity_id' => ['nullable', 'integer', 'exists:extracurricular_activity_definitions,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['integer', 'exists:students,id'],
            'records' => ['nullable', 'array'],
            'records.*.grade' => ['nullable', 'in:A,B,C,D'],
            'records.*.predicate' => ['nullable', 'in:Sangat Baik,Baik,Cukup,Kurang'],
            'records.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'participant_ids.*.exists' => 'Siswa peserta tidak valid.',
            'records.*.grade.in' => 'Nilai ekstrakurikuler harus A, B, C, atau D.',
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

            if ($this->filled('activity_id')) {
                $this->validateCurrentFlow($validator);

                return;
            }

            $this->validateLegacyFlow($validator);
        });
    }

    private function validateCurrentFlow($validator): void
    {
        $period = app()->bound('activePeriod') ? app('activePeriod') : null;
        $activity = $period
            ? ExtracurricularActivityDefinition::query()
                ->where('period_id', $period->id)
                ->where('supervisor_id', $this->user()?->id)
                ->find($this->integer('activity_id'))
            : null;

        $classroom = $period
            ? Classroom::query()
                ->whereKey($this->integer('classroom_id'))
                ->where('academic_year_id', $period->academic_year_id)
                ->first()
            : null;

        if (! $activity) {
            $validator->errors()->add('activity_id', 'Anda bukan guru pembina kegiatan ini.');
        }

        if (! $classroom) {
            $validator->errors()->add('classroom_id', 'Kelas tidak berada pada periode aktif.');
        }

        if (! $activity || ! $classroom) {
            return;
        }

        $participantIds = collect($this->input('participant_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $validParticipantIds = Enrollment::query()
            ->where('academic_year_id', $period->academic_year_id)
            ->where('classroom_id', $classroom->id)
            ->whereIn('student_id', $participantIds)
            ->pluck('student_id');

        if ($participantIds->diff($validParticipantIds)->isNotEmpty()) {
            $validator->errors()->add('participant_ids', 'Terdapat siswa yang bukan anggota kelas yang dipilih.');
        }

        $recordIds = collect(array_keys((array) $this->input('records', [])))
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($recordIds->diff($participantIds)->isNotEmpty()) {
            $validator->errors()->add('records', 'Nilai hanya boleh diisi untuk siswa yang dipilih sebagai peserta.');
        }

        if ($participantIds->diff($recordIds)->isNotEmpty()) {
            $validator->errors()->add('records', 'Setiap peserta harus memiliki nilai A, B, C, atau D.');
        }

        foreach ((array) $this->input('records', []) as $record) {
            if (blank($record['grade'] ?? null)) {
                $validator->errors()->add('records', 'Setiap peserta harus memiliki nilai A, B, C, atau D.');

                break;
            }
        }
    }

    private function validateLegacyFlow($validator): void
    {
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
            ->map(fn ($id): int => (int) $id)
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
    }
}
