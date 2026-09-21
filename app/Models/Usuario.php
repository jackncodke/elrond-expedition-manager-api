<?php

namespace App\Models;

use App\Enums\TipoUsuario;
use Carbon\CarbonImmutable;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property TipoUsuario $tipo
 * @property string $nome
 * @property string|null $codigo
 * @property string $email
 * @property string $password
 * @property bool $ativo
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table('usuarios')]
#[Fillable(['tipo', 'nome', 'codigo', 'email', 'password', 'ativo'])]
#[Hidden(['password'])]
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasApiTokens, HasFactory;

    /**
     * A autenticação é feita exclusivamente por token do Sanctum, então não há
     * coluna `remember_token` na tabela `usuarios`.
     */
    protected $rememberTokenName = '';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoUsuario::class,
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    /**
     * Expedições que este usuário solicitou, enquanto Reino.
     *
     * @return HasMany<Expedicao, $this>
     */
    public function expedicoesSolicitadas(): HasMany
    {
        return $this->hasMany(Expedicao::class, 'usuario_id');
    }

    /**
     * Expedições que este usuário deliberou, enquanto Membro do Conselho.
     *
     * @return HasMany<Expedicao, $this>
     */
    public function expedicoesDecididas(): HasMany
    {
        return $this->hasMany(Expedicao::class, 'decidido_por');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function reinos(Builder $query): Builder
    {
        return $query->where('tipo', TipoUsuario::Reino);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    #[Scope]
    protected function conselho(Builder $query): Builder
    {
        return $query->where('tipo', TipoUsuario::Conselho);
    }

    public function isReino(): bool
    {
        return $this->tipo === TipoUsuario::Reino;
    }

    public function isConselho(): bool
    {
        return $this->tipo === TipoUsuario::Conselho;
    }
}
