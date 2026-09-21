<?php

namespace App\Models;

use App\Enums\StatusExpedicao;
use Carbon\CarbonImmutable;
use Database\Factories\ExpedicaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $protocolo
 * @property int $usuario_id
 * @property StatusExpedicao $status
 * @property string|null $motivo_rejeicao
 * @property int|null $decidido_por
 * @property CarbonImmutable|null $decidido_em
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('expedicoes')]
#[RouteKey('protocolo')]
#[Fillable(['protocolo', 'usuario_id', 'status', 'motivo_rejeicao', 'decidido_por', 'decidido_em'])]
class Expedicao extends Model
{
    /** @use HasFactory<ExpedicaoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusExpedicao::class,
            'decidido_em' => 'datetime',
        ];
    }

    /**
     * O Reino solicitante da expedição.
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function reino(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * O Membro do Conselho que deliberou sobre a expedição.
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function decididoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'decidido_por');
    }

    /**
     * @return HasOne<Jornada, $this>
     */
    public function jornada(): HasOne
    {
        return $this->hasOne(Jornada::class);
    }

    /**
     * @return HasMany<Participante, $this>
     */
    public function participantes(): HasMany
    {
        return $this->hasMany(Participante::class);
    }

    /**
     * @return HasMany<ItemSuprimento, $this>
     */
    public function itensSuprimento(): HasMany
    {
        return $this->hasMany(ItemSuprimento::class);
    }

    public function finalizada(): bool
    {
        return $this->status->finalizada();
    }
}
