<?php

namespace App\Http\Requests\Homeroom;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOperator() ?? false;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'activities' => ['nullable', 'array'],
            'activities.*' => ['array'],
            'activities.*.name' => ['nullable', 'string', 'max:100'],
            'activities.*.supervisor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', Role::Guru->value)->where('is_active', true),
            ],
            'student_records' => ['nullable', 'array'],
            'student_records.*' => ['array'],
            'student_records.*.*' => ['array'],
            'student_records.*.*.activity_key' => ['nullable', 'string', 'max:100'],
            'student_records.*.*.predicate' => ['nullable', 'required_with:student_records.*.*.activity_key', 'in:Sangat Baik,Baik,Cukup,Kurang'],
            'student_records.*.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'activities.*.name.max' => 'Nama ekstrakurikuler maksimal 100 karakter.',
            'activities.*.supervisor_id.exists' => 'Guru pembina harus merupakan guru aktif.',
            'student_records.*.*.predicate.required_with' => 'Predikat wajib dipilih jika kegiatan dipilih.',
            'student_records.*.*.predicate.in' => 'Predikat ekstrakurikuler tidak valid.',
            'student_records.*.*.description.max' => 'Keterangan ekstrakurikuler maksimal 255 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('activities') || ! $this->has('extracurriculars')) {
            return;
        }

        $activities = [];
        $activityKeys = [];
        $studentRecords = [];

        foreach ((array) $this->input('extracurriculars', []) as $studentId => $records) {
            foreach ((array) $records as $record) {
                $name = trim((string) ($record['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $normalizedName = mb_strtolower($name);
                $activityKey = $activityKeys[$normalizedName] ?? null;

                if ($activityKey === null) {
                    $activityKey = 'legacy-'.count($activities);
                    $activityKeys[$normalizedName] = $activityKey;
                    $activities[$activityKey] = [
                        'name' => $name,
                        'supervisor_id' => $record['supervisor_id'] ?? null,
                    ];
                }

                $studentRecords[$studentId][] = [
                    'activity_key' => $activityKey,
                    'predicate' => $record['predicate'] ?? null,
                    'description' => $record['description'] ?? null,
                ];
            }
        }

        $this->merge([
            'activities' => $activities,
            'student_records' => $studentRecords,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $period = app()->bound('activePeriod') ? app('activePeriod') : null;
            $classroom = null;
            if ($period && $this->user()?->isOperator()) {
                $classroom = Classroom::where('academic_year_id', $period->academic_year_id)
                    ->whereKey((int) $this->input('classroom_id'))
                    ->first();
            }

            if (! $classroom) {
                $validator->errors()->add('classroom_id', 'Kelas tidak sesuai dengan periode aktif.');

                return;
            }

            $studentIds = array_map('intval', array_keys((array) $this->input('student_records', [])));
            $validStudentIds = Enrollment::query()
                ->where('classroom_id', $classroom->id)
                ->where('academic_year_id', $period->academic_year_id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->all();

            if (array_diff($studentIds, $validStudentIds)) {
                $validator->errors()->add('extracurriculars', 'Terdapat siswa yang bukan anggota kelas yang dipilih.');
            }

            $activities = (array) $this->input('activities', []);
            $namedActivityKeys = collect($activities)
                ->filter(fn ($activity) => filled($activity['name'] ?? null))
                ->keys()
                ->map(fn ($key): string => (string) $key)
                ->all();
            $activityNames = collect($activities)
                ->map(fn ($activity) => mb_strtolower(trim((string) ($activity['name'] ?? ''))))
                ->filter()
                ->values();

            if ($activityNames->count() !== $activityNames->unique()->count()) {
                $validator->errors()->add('activities', 'Nama ekstrakurikuler tidak boleh duplikat dalam satu kelas.');
            }

            foreach ($activities as $activity) {
                $nameFilled = filled($activity['name'] ?? null);
                $supervisorFilled = filled($activity['supervisor_id'] ?? null);

                if ($nameFilled && ! $supervisorFilled) {
                    $validator->errors()->add('activities', 'Guru pembina wajib dipilih untuk setiap kegiatan.');
                    break;
                }

                if (! $nameFilled && $supervisorFilled) {
                    $validator->errors()->add('activities', 'Nama ekstrakurikuler wajib diisi jika pembina dipilih.');
                    break;
                }
            }

            foreach ((array) $this->input('student_records', []) as $studentRecords) {
                $selectedActivities = collect((array) $studentRecords)
                    ->map(fn ($record) => (string) ($record['activity_key'] ?? ''))
                    ->filter()
                    ->values();

                if ($selectedActivities->count() !== $selectedActivities->unique()->count()) {
                    $validator->errors()->add('student_records', 'Kegiatan ekstrakurikuler tidak boleh duplikat untuk siswa yang sama.');
                    break;
                }

                foreach ($selectedActivities as $activityKey) {
                    if (! in_array($activityKey, $namedActivityKeys, true)) {
                        $validator->errors()->add('student_records', 'Terdapat kegiatan ekstrakurikuler yang tidak terdaftar.');

                        return;
                    }
                }
            }
        });
    }
}
