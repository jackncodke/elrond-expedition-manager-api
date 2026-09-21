<?php

namespace App\Actions;

use App\Enums\StatusExpedicao;
use App\Exceptions\ExpedicaoJaFinalizada;
use App\Models\Expedicao;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Registra a deliberação do Conselho sobre uma expedição.
 */
class DecidirExpedicao
{
    public function handle(
        Expedicao $expedicao,
        Usuario $conselheiro,
        StatusExpedicao $decisao,
        ?string $motivo = null,
    ): Expedicao {
        return DB::transaction(function () use ($expedicao, $conselheiro, $decisao, $motivo): Expedicao {
            // Relê a linha travada dentro da transação: sem isso, duas decisões
            // simultâneas poderiam passar as duas pela checagem de status.
            $travada = Expedicao::whereKey($expedicao->getKey())->lockForUpdate()->firstOrFail();

            if ($travada->finalizada()) {
                throw new ExpedicaoJaFinalizada($travada);
            }

            $travada->update([
                'status' => $decisao,
                'motivo_rejeicao' => $decisao === StatusExpedicao::Rejeitada ? $motivo : null,
                'decidido_por' => $conselheiro->id,
                'decidido_em' => now(),
            ]);

            return $travada;
        });
    }
}
