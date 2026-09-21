<?php

namespace App\Support;

use App\Models\Expedicao;

/**
 * Monta o protocolo público de uma expedição no formato PREFIXO-ANO-SEQUENCIAL
 * (ex.: ELR-2026-000123). A sequência reinicia a cada ano.
 *
 * A unicidade real é garantida pelo índice único de `expedicoes.protocolo`;
 * quem chama deve tratar a colisão e pedir o próximo, como faz CriarExpedicao.
 */
class GeradorDeProtocolo
{
    public const TAMANHO_DO_SEQUENCIAL = 6;

    public function proximo(?int $ano = null): string
    {
        $ano ??= (int) now()->year;
        $prefixoDoAno = $this->prefixo().'-'.$ano.'-';

        $ultimo = Expedicao::where('protocolo', 'like', $prefixoDoAno.'%')
            ->orderByDesc('protocolo')
            ->value('protocolo');

        $sequencial = $ultimo === null
            ? 1
            : ((int) substr($ultimo, strlen($prefixoDoAno))) + 1;

        return $prefixoDoAno.str_pad((string) $sequencial, self::TAMANHO_DO_SEQUENCIAL, '0', STR_PAD_LEFT);
    }

    protected function prefixo(): string
    {
        return (string) config('conselho.protocolo.prefixo', 'ELR');
    }
}
