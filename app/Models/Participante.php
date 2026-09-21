<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ParticipanteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $expedicao_id
 * @property string $nome
 * @property string $raca
 * @property string $papel
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['expedicao_id', 'nome', 'raca', 'papel'])]
class Participante extends Model
{
    /** @use HasFactory<ParticipanteFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Expedicao, $this>
     */
    public function expedicao(): BelongsTo
    {
        return $this->belongsTo(Expedicao::class);
    }
}
