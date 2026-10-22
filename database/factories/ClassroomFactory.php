<?php

namespace Database\Factories;

use App\Enums\Curriculum;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'level' => 7,
            'name' => 'VII.'.fake()->randomElement(['A', 'B', 'C', 'D']),
            'alias' => null,
            'curriculum' => Curriculum::Merdeka,
            'phase' => 'D',
            'homeroom_teacher_id' => User::factory()->guru(),
        ];
    }
}
