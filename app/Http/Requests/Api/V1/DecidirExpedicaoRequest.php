<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\StatusExpedicao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecidirExpedicaoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'decisao' => [
                'required',
                Rule::enum(StatusExpedicao::class)->only([
                    StatusExpedicao::Autorizada,
                    StatusExpedicao::Rejeitada,
                ]),
            ],

            // `exclude_unless` descarta o motivo quando a decisão é AUTORIZADA,
            // então ele nunca acaba gravado numa expedição autorizada.
            'motivo' => ['exclude_unless:decisao,'.StatusExpedicao::Rejeitada->value, 'required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decisao.required' => 'Informe a decisão do Conselho.',
            'motivo.required' => 'O motivo é obrigatório para rejeitar uma expedição.',
        ];
    }

    public function decisao(): StatusExpedicao
    {
        return StatusExpedicao::from($this->string('decisao')->value());
    }

    public function motivo(): ?string
    {
        $motivo = $this->safe()->string('motivo', '')->trim();

        return $motivo->isEmpty() ? null : $motivo->value();
    }
}
