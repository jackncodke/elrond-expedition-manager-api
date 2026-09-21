<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusExpedicaoResource;
use App\Models\Expedicao;

class StatusExpedicaoController extends Controller
{
    /**
     * Consulta pública do andamento de uma expedição.
     */
    public function show(Expedicao $expedicao): StatusExpedicaoResource
    {
        return new StatusExpedicaoResource($expedicao);
    }
}
