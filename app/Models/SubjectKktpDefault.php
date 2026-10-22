<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectKktpDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'subject_id',
        'level',
        'kktp',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'kktp' => 'decimal:2',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
