<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nis' => fake()->numerify('24#####'),
            'nisn' => fake()->unique()->numerify('01########'),
            'name' => strtoupper(fake()->name()),
            'gender' => fake()->randomElement(['L', 'P']),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->date('Y-m-d', '-13 years'),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
