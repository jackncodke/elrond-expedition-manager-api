<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\JornadaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $expedicao_id
 * @property string $origem
 * @property string $destino
 * @property CarbonImmutable $data_partida_prevista
 * @property string|null $rota_descricao
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['expedicao_id', 'origem', 'destino', 'data_partida_prevista', 'rota_descricao'])]
class Jornada extends Model
{
    /** @use HasFactory<JornadaFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_partida_prevista' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Expedicao, $this>
     */
    public function expedicao(): BelongsTo
    {
        return $this->belongsTo(Expedicao::class);
    }
}
