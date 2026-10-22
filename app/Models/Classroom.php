<?php

namespace App\Models;

use App\Enums\Curriculum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'level',
        'name',
        'alias',
        'curriculum',
        'phase',
        'homeroom_teacher_id',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'curriculum' => Curriculum::class,
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function locks(): HasMany
    {
        return $this->hasMany(ClassroomLock::class);
    }

    public function isLockedInPeriod(int $periodId): bool
    {
        return $this->locks()->where('period_id', $periodId)->exists();
    }
}
