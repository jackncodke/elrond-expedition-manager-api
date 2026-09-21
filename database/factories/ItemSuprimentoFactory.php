<?php

namespace Database\Factories;

use App\Models\Expedicao;
use App\Models\ItemSuprimento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemSuprimento>
 */
class ItemSuprimentoFactory extends Factory
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
            'nome' => fake()->words(2, true),
            'tipo' => fake()->randomElement(['arma', 'provisao', 'artefato_magico', 'montaria']),
            'quantidade' => fake()->numberBetween(1, 50),
            'observacoes' => fake()->optional()->sentence(),
        ];
    }
}
