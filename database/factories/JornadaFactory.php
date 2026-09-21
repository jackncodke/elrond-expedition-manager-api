<?php

namespace Database\Factories;

use App\Models\Expedicao;
use App\Models\Jornada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jornada>
 */
class JornadaFactory extends Factory
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
            'origem' => fake()->city(),
            'destino' => fake()->city(),
            'data_partida_prevista' => fake()->dateTimeBetween('+1 week', '+6 months')->format('Y-m-d'),
            'rota_descricao' => fake()->optional()->paragraph(),
        ];
    }
}
