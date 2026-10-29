<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtracurricularRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'classroom_id',
        'student_id',
        'supervisor_id',
        'extracurricular_activity_id',
        'activity_definition_id',
        'name',
        'predicate',
        'description',
    ];

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

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ExtracurricularActivity::class, 'extracurricular_activity_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ExtracurricularActivityDefinition::class, 'activity_definition_id');
    }
}
