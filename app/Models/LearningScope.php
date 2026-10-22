<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'teaching_assignment_id',
        'sequence',
        'summary',
        'kktp',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'kktp' => 'decimal:2',
        ];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(SummativeScore::class);
    }
}
