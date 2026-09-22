<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CriarExpedicao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExpedicaoRequest;
use App\Http\Resources\Api\V1\ExpedicaoResource;
use App\Models\Expedicao;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExpedicaoController extends Controller
{
    /**
     * Relações que compõem a visão completa de uma expedição.
     *
     * @var list<string>
     */
    public const RELACOES = ['reino', 'jornada', 'participantes', 'itensSuprimento', 'decididoPor'];

    /**
     * Consulta pública de uma expedição pelo seu protocolo.
     */
    public function show(Expedicao $expedicao): ExpedicaoResource
    {
        return new ExpedicaoResource($expedicao->load(self::RELACOES));
    }

    /**
     * Registra uma expedição para o Reino autenticado.
     */
    public function store(StoreExpedicaoRequest $request, CriarExpedicao $criar): JsonResponse
    {
        /** @var Usuario $reino */
        $reino = $request->user();

        $expedicao = $criar->handle($reino, $request->dadosDaExpedicao());

        $expedicao->load(self::RELACOES);

        return (new ExpedicaoResource($expedicao))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
