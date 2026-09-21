<?php

namespace App\Exceptions;

use App\Models\Expedicao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deliberação do Conselho é definitiva: uma expedição já autorizada ou já
 * rejeitada não aceita uma nova decisão.
 */
class ExpedicaoJaFinalizada extends RuntimeException
{
    public function __construct(public readonly Expedicao $expedicao)
    {
        parent::__construct("A expedição {$expedicao->protocolo} já foi {$expedicao->status->value} e não aceita uma nova decisão.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'protocolo' => $this->expedicao->protocolo,
            'status' => $this->expedicao->status->value,
        ], Response::HTTP_CONFLICT);
    }
}
