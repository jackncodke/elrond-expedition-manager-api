<?php

namespace Database\Factories;

use App\Models\Expedicao;
use App\Models\Participante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participante>
 */
class ParticipanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expedicao_id' => Expedicao::factory(),
            'nome' => fake()->name(),
            'raca' => fake()->randomElement(['Humano', 'Elfo', 'Anão', 'Hobbit', 'Mago', 'Ent']),
            'papel' => fake()->randomElement(['Líder', 'Batedor', 'Guarda', 'Curandeiro', 'Portador', 'Guia']),
        ];
    }
}
