<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StatusExpedicaoResource;
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
