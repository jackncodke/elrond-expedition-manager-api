<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Perfil de um usuário da API. A tabela `usuarios` é única e este campo é o
 * que distingue um Reino solicitante de um Membro do Conselho.
 */
enum TipoUsuario: string
{
    case Reino = 'REINO';
    case Conselho = 'CONSELHO';

    /**
     * Validade, em minutos, do token do Sanctum emitido para este perfil.
     */
    public function minutosDeExpiracaoDoToken(): int
    {
        return (int) config("conselho.autenticacao.expiracao_em_minutos.{$this->value}");
    }

    /**
     * Instante em que expira um token emitido agora para este perfil.
     */
    public function expiracaoDoToken(): CarbonImmutable
    {
        return Date::now()->addMinutes($this->minutosDeExpiracaoDoToken());
    }
}
