<?php

namespace App\Http\Resources;

use App\Models\Participante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Participante
 */
class ParticipanteResource extends JsonResource
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
            'raca' => $this->raca,
            'papel' => $this->papel,
        ];
    }
}
