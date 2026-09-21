<?php

use App\Models\Usuario;

/**
 * Guarda contra regressão: a API responde em português, inclusive nas
 * mensagens genéricas do framework. Uma regra de validação nova sem tradução
 * cairia no inglês do fallback e seria pega aqui.
 */
it('responde as falhas de validacao em portugues', function () {
    $resposta = $this->actingAs(Usuario::factory()->reino()->create(), 'sanctum')
        ->postJson(route('expedicoes.store'), [
            'jornada' => ['origem' => '', 'destino' => str_repeat('a', 200)],
            'participantes' => [['nome' => 'Faramir']],
            'itens_suprimento' => [['tipo' => 'arma', 'quantidade' => 0]],
        ]);

    $resposta->assertUnprocessable();

    $mensagens = collect($resposta->json('errors'))->flatten();

    expect($mensagens)->not->toBeEmpty();

    $mensagens->each(function (string $mensagem): void {
        expect($mensagem)
            ->not->toStartWith('The ')
            ->and($mensagem)->not->toContain(' field is ')
            ->and($mensagem)->not->toContain(' must be ');
    });
});

it('usa nomes legiveis de campo em vez do caminho aninhado', function () {
    $resposta = $this->actingAs(Usuario::factory()->reino()->create(), 'sanctum')
        ->postJson(route('expedicoes.store'), [
            'jornada' => ['origem' => 'Edoras', 'destino' => 'Isengard'],
            'participantes' => [['nome' => 'Éomer', 'raca' => 'Humano', 'papel' => 'Líder']],
            'itens_suprimento' => [],
        ]);

    $resposta->assertUnprocessable();

    // A chave contém pontos, então é lida direto do array em vez de por
    // caminho de json().
    $erros = $resposta->json('errors');

    expect($erros['jornada.data_partida_prevista'][0])
        ->toBe('O campo data de partida prevista é obrigatório.');
});

it('responde as credenciais invalidas em portugues', function () {
    $resposta = $this->postJson(route('login'), [
        'email' => 'ninguem@elrond.test',
        'password' => 'seja-la-o-que-for',
    ]);

    expect($resposta->json('errors.email.0'))->toBe(__('auth.failed'))
        ->and(__('auth.failed'))->toBe('As credenciais informadas são inválidas.');
});
