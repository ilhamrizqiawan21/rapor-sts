<?php

namespace App\Models;

use App\Enums\Curriculum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'group',
        'curriculum',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'curriculum' => Curriculum::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Subject::class, 'parent_id')->orderBy('sort_order');
    }

    public function isParent(): bool
    {
        return $this->children()->exists();
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function kktpDefaults(): HasMany
    {
        return $this->hasMany(SubjectKktpDefault::class);
    }
}
