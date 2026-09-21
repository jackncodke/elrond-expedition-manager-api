<?php

namespace Database\Factories;

use App\Enums\StatusExpedicao;
use App\Models\Expedicao;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expedicao>
 */
class ExpedicaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'protocolo' => sprintf(
                '%s-%s-%06d',
                config('conselho.protocolo.prefixo'),
                now()->year,
                fake()->unique()->numberBetween(1, 999999),
            ),
            'usuario_id' => Usuario::factory()->reino(),
            'status' => StatusExpedicao::Pendente,
            'motivo_rejeicao' => null,
            'decidido_por' => null,
            'decidido_em' => null,
        ];
    }

    public function autorizada(?Usuario $decididoPor = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusExpedicao::Autorizada,
            'motivo_rejeicao' => null,
            'decidido_por' => $decididoPor ?? Usuario::factory()->conselho(),
            'decidido_em' => now(),
        ]);
    }

    public function rejeitada(?string $motivo = null, ?Usuario $decididoPor = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => StatusExpedicao::Rejeitada,
            'motivo_rejeicao' => $motivo ?? fake()->sentence(),
            'decidido_por' => $decididoPor ?? Usuario::factory()->conselho(),
            'decidido_em' => now(),
        ]);
    }

    /**
     * Uma expedição com jornada, participantes e itens de suprimento.
     */
    public function completa(int $participantes = 3, int $itens = 2): static
    {
        return $this
            ->has(JornadaFactory::new(), 'jornada')
            ->has(ParticipanteFactory::new()->count($participantes), 'participantes')
            ->has(ItemSuprimentoFactory::new()->count($itens), 'itensSuprimento');
    }
}
