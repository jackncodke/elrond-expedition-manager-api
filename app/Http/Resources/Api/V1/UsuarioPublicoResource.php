<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Identifica um usuário dentro de uma expedição sem expor dados de conta.
 *
 * A consulta por protocolo é pública, então `email` e `ativo` ficam de fora:
 * quem acompanha uma expedição precisa saber qual Reino a solicitou e qual
 * Membro do Conselho a deliberou, não como entrar na conta deles.
 *
 * @mixin Usuario
 */
class UsuarioPublicoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'nome' => $this->nome,
            'codigo' => $this->codigo,
        ];
    }
}
