<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtracurricularActivityDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'name',
        'supervisor_id',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
        ];
    }

    public function isMandatory(): bool
    {
        return (bool) $this->is_mandatory;
    }

    public function typeLabel(): string
    {
        return $this->isMandatory() ? 'Wajib' : 'Pilihan';
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ExtracurricularRecord::class, 'activity_definition_id');
    }
}
