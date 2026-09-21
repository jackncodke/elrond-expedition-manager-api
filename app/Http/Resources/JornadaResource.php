<?php

namespace App\Http\Resources;

use App\Models\Jornada;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Jornada
 */
class JornadaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'origem' => $this->origem,
            'destino' => $this->destino,
            'data_partida_prevista' => $this->data_partida_prevista->toDateString(),
            'rota_descricao' => $this->rota_descricao,
        ];
    }
}
