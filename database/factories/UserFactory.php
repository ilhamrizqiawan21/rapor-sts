<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Guru,
            'nik' => fake()->numerify('3204############'),
            'nuptk' => fake()->numerify('################'),
            'nip' => null,
            'gender' => fake()->randomElement(['L', 'P']),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function operator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Operator,
        ]);
    }

    public function guru(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Guru,
        ]);
    }

    public function kepala(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Kepala,
            'nip' => fake()->numerify('19700101########'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
