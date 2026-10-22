<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'password',
        'password_plain',
        'role',
        'nik',
        'nuptk',
        'nip',
        'gender',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'password_plain',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'password_plain' => 'encrypted',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function isOperator(): bool
    {
        return $this->role === Role::Operator;
    }

    public function isGuru(): bool
    {
        return $this->role === Role::Guru;
    }

    public function isKepala(): bool
    {
        return $this->role === Role::Kepala;
    }

    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(Classroom::class, 'homeroom_teacher_id');
    }

    public function isHomeroomTeacher(?int $classroomId = null, ?int $academicYearId = null): bool
    {
        if (! $this->isGuru()) {
            return false;
        }

        $query = $this->homeroomClasses();

        if ($classroomId) {
            $query->where('id', $classroomId);
        }

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->exists();
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
