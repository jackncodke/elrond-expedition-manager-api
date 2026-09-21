<?php

use App\Enums\StatusExpedicao;
use App\Http\Middleware\EnsureRequestIsInternal;
use App\Models\Expedicao;
use App\Models\Usuario;

it('devolve o status de uma expedicao pendente', function () {
    $expedicao = Expedicao::factory()->completa()->create();

    $this->getJson(route('expedicoes.status', $expedicao->protocolo))
        ->assertOk()
        ->assertExactJson([
            'protocolo' => $expedicao->protocolo,
            'status' => StatusExpedicao::Pendente->value,
            'finalizada' => false,
            'motivo_rejeicao' => null,
            'decidido_em' => null,
            'criado_em' => $expedicao->created_at?->toIso8601String(),
            'atualizado_em' => $expedicao->updated_at?->toIso8601String(),
        ]);
});

it('devolve o status de uma expedicao autorizada', function () {
    $expedicao = Expedicao::factory()->autorizada()->create();

    $this->getJson(route('expedicoes.status', $expedicao->protocolo))
        ->assertOk()
        ->assertJsonPath('status', StatusExpedicao::Autorizada->value)
        ->assertJsonPath('finalizada', true)
        ->assertJsonPath('motivo_rejeicao', null)
        ->assertJsonPath('decidido_em', $expedicao->decidido_em?->toIso8601String());
});

it('devolve o status e o motivo de uma expedicao rejeitada', function () {
    $expedicao = Expedicao::factory()->rejeitada('Rota sob domínio inimigo.')->create();

    $this->getJson(route('expedicoes.status', $expedicao->protocolo))
        ->assertOk()
        ->assertJsonPath('status', StatusExpedicao::Rejeitada->value)
        ->assertJsonPath('finalizada', true)
        ->assertJsonPath('motivo_rejeicao', 'Rota sob domínio inimigo.')
        ->assertJsonPath('decidido_em', $expedicao->decidido_em?->toIso8601String());
});

it('consulta o status sem autenticacao', function () {
    $expedicao = Expedicao::factory()->create();

    $this->getJson(route('expedicoes.status', $expedicao->protocolo))->assertOk();
});

it('devolve 404 para um protocolo que nao existe', function () {
    $this->getJson(route('expedicoes.status', 'ELR-2026-999999'))
        ->assertNotFound()
        ->assertJsonPath('message', 'Nenhuma expedição encontrada para o protocolo informado.');
});

it('acompanha a mudanca de status apos a decisao do conselho', function () {
    $expedicao = Expedicao::factory()->completa()->create();
    $url = route('expedicoes.status', $expedicao->protocolo);

    $this->getJson($url)->assertJsonPath('status', StatusExpedicao::Pendente->value);

    $this->actingAs(Usuario::factory()->conselho()->create(), 'sanctum')
        ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->patchJson(
            route('expedicoes.decisao', $expedicao->protocolo),
            ['decisao' => StatusExpedicao::Rejeitada->value, 'motivo' => 'Perigosa demais.'],
            [EnsureRequestIsInternal::HEADER_TOKEN => config('conselho.interno.token_de_servico')],
        )->assertOk();

    $this->getJson($url)
        ->assertJsonPath('status', StatusExpedicao::Rejeitada->value)
        ->assertJsonPath('finalizada', true)
        ->assertJsonPath('motivo_rejeicao', 'Perigosa demais.')
        ->assertJsonPath('decidido_em', fn (?string $valor): bool => $valor !== null);
});

it('nao expoe dados do reino nem da jornada', function () {
    $reino = Usuario::factory()->reino()->create(['nome' => 'Reino de Gondor', 'email' => 'gondor@elrond.test']);
    $expedicao = Expedicao::factory()->for($reino, 'reino')->completa()->create();

    $resposta = $this->getJson(route('expedicoes.status', $expedicao->protocolo));

    expect($resposta->json())->not->toHaveKeys(['reino', 'jornada', 'participantes', 'itens_suprimento'])
        ->and($resposta->content())->not->toContain('gondor@elrond.test');
});

it('consulta o status com uma unica consulta ao banco', function () {
    $expedicao = Expedicao::factory()->completa(participantes: 10, itens: 10)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson(route('expedicoes.status', $expedicao->protocolo))->assertOk();

    expect(DB::getQueryLog())->toHaveCount(1);
});
