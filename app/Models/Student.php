<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'nisn',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function summativeScores(): HasMany
    {
        return $this->hasMany(SummativeScore::class);
    }

    public function stsScores(): HasMany
    {
        return $this->hasMany(StsScore::class);
    }

    public function competencyOverrides(): HasMany
    {
        return $this->hasMany(CompetencyOverride::class);
    }

    public function homeroomRecords(): HasMany
    {
        return $this->hasMany(HomeroomRecord::class);
    }

    public function extracurricularRecords(): HasMany
    {
        return $this->hasMany(ExtracurricularRecord::class);
    }
}
