<?php

namespace App\Http\Controllers;

use App\Actions\CriarExpedicao;
use App\Http\Requests\StoreExpedicaoRequest;
use App\Http\Resources\ExpedicaoResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExpedicaoController extends Controller
{
    /**
     * Registra uma expedição para o Reino autenticado.
     */
    public function store(StoreExpedicaoRequest $request, CriarExpedicao $criar): JsonResponse
    {
        /** @var Usuario $reino */
        $reino = $request->user();

        $expedicao = $criar->handle($reino, $request->dadosDaExpedicao());

        $expedicao->load(['reino', 'jornada', 'participantes', 'itensSuprimento', 'decididoPor']);

        return (new ExpedicaoResource($expedicao))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
