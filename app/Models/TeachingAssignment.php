<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'classroom_id',
        'subject_id',
        'teacher_id',
        'hours_per_week',
        'weight_summative',
        'weight_sts',
        'kktp',
        'submitted_at',
        'submitted_by',
    ];

    protected function casts(): array
    {
        return [
            'hours_per_week' => 'integer',
            'weight_summative' => 'decimal:2',
            'weight_sts' => 'decimal:2',
            'kktp' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function learningScopes(): HasMany
    {
        return $this->hasMany(LearningScope::class)->orderBy('sequence');
    }

    public function stsScores(): HasMany
    {
        return $this->hasMany(StsScore::class);
    }

    public function competencyOverrides(): HasMany
    {
        return $this->hasMany(CompetencyOverride::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
