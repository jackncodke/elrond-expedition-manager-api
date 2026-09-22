<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Expedicao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Visão enxuta de uma expedição: só o andamento.
 *
 * Diferente de ExpedicaoResource, não carrega jornada, participantes nem itens
 * — quem só quer acompanhar o status não deveria pagar por essas consultas.
 *
 * @mixin Expedicao
 */
class StatusExpedicaoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'protocolo' => $this->protocolo,
            'status' => $this->status->value,
            'finalizada' => $this->finalizada(),
            'motivo_rejeicao' => $this->motivo_rejeicao,
            'decidido_em' => $this->decidido_em?->toIso8601String(),
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
