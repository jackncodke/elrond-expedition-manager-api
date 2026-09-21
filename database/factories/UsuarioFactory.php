<?php

namespace Database\Factories;

use App\Enums\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
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
            'tipo' => TipoUsuario::Reino,
            'nome' => fake()->company(),
            'codigo' => Str::upper(fake()->unique()->bothify('???-###')),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'ativo' => true,
        ];
    }

    /**
     * Um Reino solicitante, que possui `codigo`.
     */
    public function reino(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipo' => TipoUsuario::Reino,
            'nome' => fake()->unique()->city(),
            'codigo' => Str::upper(fake()->unique()->bothify('???-###')),
        ]);
    }

    /**
     * Um Membro do Conselho, que nunca possui `codigo`.
     */
    public function conselho(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tipo' => TipoUsuario::Conselho,
            'nome' => fake()->name(),
            'codigo' => null,
        ]);
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ativo' => false,
        ]);
    }
}
