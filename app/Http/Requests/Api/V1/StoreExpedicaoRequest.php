<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpedicaoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            // O Reino solicitante vem sempre do token autenticado. Recusar o
            // campo explicitamente deixa claro que não dá para forjá-lo.
            'usuario_id' => ['prohibited'],
            'protocolo' => ['prohibited'],
            'status' => ['prohibited'],

            'jornada' => ['required', 'array'],
            'jornada.origem' => ['required', 'string', 'max:120'],
            'jornada.destino' => ['required', 'string', 'max:120'],
            // Uma expedição só é registrada antes de partir; `today` permite
            // registrar uma que parte no mesmo dia.
            'jornada.data_partida_prevista' => ['required', 'date', 'after_or_equal:today'],
            'jornada.rota_descricao' => ['nullable', 'string'],

            'participantes' => ['required', 'array', 'min:1'],
            'participantes.*.nome' => ['required', 'string', 'max:120'],
            'participantes.*.raca' => ['required', 'string', 'max:60'],
            'participantes.*.papel' => ['required', 'string', 'max:60'],

            'itens_suprimento' => ['present', 'array'],
            'itens_suprimento.*.nome' => ['required', 'string', 'max:120'],
            'itens_suprimento.*.tipo' => ['required', 'string', 'max:40'],
            'itens_suprimento.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens_suprimento.*.observacoes' => ['nullable', 'string'],
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
            'usuario_id.prohibited' => 'O Reino solicitante é determinado pelo token autenticado e não pode ser enviado.',
            'protocolo.prohibited' => 'O protocolo é gerado pela API e não pode ser enviado.',
            'status.prohibited' => 'O status inicial é sempre PENDENTE e não pode ser enviado.',
            // Uma lista vazia falha em `required`, não em `min`: as duas
            // chaves precisam da mensagem, senão `[]` cai no texto genérico.
            'participantes.required' => 'A expedição precisa de ao menos um participante.',
            'participantes.min' => 'A expedição precisa de ao menos um participante.',
            'jornada.data_partida_prevista.after_or_equal' => 'A data de partida prevista não pode estar no passado.',
        ];
    }

    /**
     * Apenas os dados que a criação usa, já no formato esperado pela action.
     *
     * @return array{jornada: array<string, mixed>, participantes: list<array<string, mixed>>, itens_suprimento: list<array<string, mixed>>}
     */
    public function dadosDaExpedicao(): array
    {
        $validado = $this->safe();

        return [
            'jornada' => $validado->array('jornada'),
            'participantes' => array_values($validado->array('participantes')),
            'itens_suprimento' => array_values($validado->array('itens_suprimento')),
        ];
    }
}
