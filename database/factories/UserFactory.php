<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'rol' => Rol::Visualizador,
            'activo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Administrador,
        ]);
    }

    public function operador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Operador,
        ]);
    }

    public function visualizador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Visualizador,
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
