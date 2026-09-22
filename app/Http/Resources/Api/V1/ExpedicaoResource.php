<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Expedicao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Expedicao
 */
class ExpedicaoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * A forma de dois argumentos de `whenLoaded` é o que faz uma relação
     * carregada mas vazia — `decididoPor` de uma expedição pendente — sair
     * como `null` em vez de sumir da resposta.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'protocolo' => $this->protocolo,
            'status' => $this->status->value,
            'motivo_rejeicao' => $this->motivo_rejeicao,
            'reino' => $this->whenLoaded('reino', fn (): UsuarioPublicoResource => new UsuarioPublicoResource($this->reino)),
            'jornada' => $this->whenLoaded('jornada', fn (): JornadaResource => new JornadaResource($this->jornada)),
            'participantes' => ParticipanteResource::collection($this->whenLoaded('participantes')),
            'itens_suprimento' => ItemSuprimentoResource::collection($this->whenLoaded('itensSuprimento')),
            'decidido_por' => $this->whenLoaded('decididoPor', fn (): UsuarioPublicoResource => new UsuarioPublicoResource($this->decididoPor)),
            'decidido_em' => $this->decidido_em?->toIso8601String(),
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
