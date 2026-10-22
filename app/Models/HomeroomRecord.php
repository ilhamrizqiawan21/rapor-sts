<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeroomRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'classroom_id',
        'student_id',
        'sick',
        'permit',
        'absent',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'sick' => 'integer',
            'permit' => 'integer',
            'absent' => 'integer',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
