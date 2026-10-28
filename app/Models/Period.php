<?php

namespace App\Models;

use App\Enums\Semester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Period extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'semester',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function homeroomRecords(): HasMany
    {
        return $this->hasMany(HomeroomRecord::class);
    }

    public function classroomLocks(): HasMany
    {
        return $this->hasMany(ClassroomLock::class);
    }

    public function extracurricularRecords(): HasMany
    {
        return $this->hasMany(ExtracurricularRecord::class);
    }

    public function achievementRecords(): HasMany
    {
        return $this->hasMany(AchievementRecord::class);
    }

    public function printSetting(): HasOne
    {
        return $this->hasOne(PrintSetting::class);
    }

    public function getBadgeTextAttribute(): string
    {
        return 'SEMESTER '.strtoupper($this->semester->label()).' '.($this->academicYear?->name ?? '');
    }
}
