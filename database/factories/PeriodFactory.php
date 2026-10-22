<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Period>
 */
class PeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'semester' => Semester::Ganjil,
            'is_active' => true,
        ];
    }

    public function ganjil(): static
    {
        return $this->state(fn (array $attributes) => [
            'semester' => Semester::Ganjil,
        ]);
    }

    public function genap(): static
    {
        return $this->state(fn (array $attributes) => [
            'semester' => Semester::Genap,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
