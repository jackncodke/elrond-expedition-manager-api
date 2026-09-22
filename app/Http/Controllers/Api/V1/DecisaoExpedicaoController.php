<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\DecidirExpedicao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DecidirExpedicaoRequest;
use App\Http\Resources\Api\V1\ExpedicaoResource;
use App\Models\Expedicao;
use App\Models\Usuario;

class DecisaoExpedicaoController extends Controller
{
    /**
     * Registra a deliberação do Conselho sobre uma expedição.
     *
     * Rota interna: a proteção é feita pelos middlewares `interno`,
     * `auth:sanctum` e `conselho`, nessa ordem.
     */
    public function update(
        DecidirExpedicaoRequest $request,
        Expedicao $expedicao,
        DecidirExpedicao $decidir,
    ): ExpedicaoResource {
        /** @var Usuario $conselheiro */
        $conselheiro = $request->user();

        $decidida = $decidir->handle(
            $expedicao,
            $conselheiro,
            $request->decisao(),
            $request->motivo(),
        );

        return new ExpedicaoResource($decidida->load(ExpedicaoController::RELACOES));
    }
}
