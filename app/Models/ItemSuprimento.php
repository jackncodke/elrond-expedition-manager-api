<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ItemSuprimentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $expedicao_id
 * @property string $nome
 * @property string $tipo
 * @property int $quantidade
 * @property string|null $observacoes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('itens_suprimento')]
#[Fillable(['expedicao_id', 'nome', 'tipo', 'quantidade', 'observacoes'])]
class ItemSuprimento extends Model
{
    /** @use HasFactory<ItemSuprimentoFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
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
