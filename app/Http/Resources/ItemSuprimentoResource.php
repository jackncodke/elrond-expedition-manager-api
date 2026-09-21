<?php

namespace App\Http\Resources;

use App\Models\ItemSuprimento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ItemSuprimento
 */
class ItemSuprimentoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nome' => $this->nome,
            'tipo' => $this->tipo,
            'quantidade' => $this->quantidade,
            'observacoes' => $this->observacoes,
        ];
    }
}
