<?php

use App\Enums\StatusExpedicao;
use App\Models\Expedicao;
use App\Models\Usuario;

it('consulta uma expedicao pelo protocolo sem autenticacao', function () {
    $reino = Usuario::factory()->reino()->create(['nome' => 'Reino de Gondor', 'codigo' => 'GONDOR']);
    $expedicao = Expedicao::factory()->for($reino, 'reino')->completa(participantes: 3, itens: 2)->create();

    $this->getJson(route('v1.expedicoes.show', $expedicao->protocolo))
        ->assertOk()
        ->assertJsonPath('protocolo', $expedicao->protocolo)
        ->assertJsonPath('status', StatusExpedicao::Pendente->value)
        ->assertJsonPath('reino.nome', 'Reino de Gondor')
        ->assertJsonPath('reino.codigo', 'GONDOR')
        ->assertJsonCount(3, 'participantes')
        ->assertJsonCount(2, 'itens_suprimento')
        ->assertJsonStructure([
            'protocolo', 'status', 'motivo_rejeicao', 'decidido_em', 'criado_em', 'atualizado_em',
            'reino' => ['id', 'tipo', 'nome', 'codigo'],
            'jornada' => ['origem', 'destino', 'data_partida_prevista', 'rota_descricao'],
            'participantes' => [['nome', 'raca', 'papel']],
            'itens_suprimento' => [['nome', 'tipo', 'quantidade', 'observacoes']],
        ]);
});

it('devolve 404 para um protocolo que nao existe', function () {
    $this->getJson(route('v1.expedicoes.show', 'ELR-2026-999999'))
        ->assertNotFound()
        ->assertJsonPath('message', 'Nenhuma expedição encontrada para o protocolo informado.');
});

it('nao revela o nome da classe do model no 404', function () {
    $resposta = $this->getJson(route('v1.expedicoes.show', 'ELR-2026-999999'));

    expect($resposta->content())->not->toContain('App\\Models')
        ->and($resposta->content())->not->toContain('Expedicao]');
});

it('devolve a ultima atualizacao da expedicao', function () {
    $expedicao = Expedicao::factory()->completa()->create();

    $this->getJson(route('v1.expedicoes.show', $expedicao->protocolo))
        ->assertOk()
        ->assertJsonPath('atualizado_em', $expedicao->updated_at?->toIso8601String())
        ->assertJsonPath('criado_em', $expedicao->created_at?->toIso8601String());
});

it('mostra o motivo e quem decidiu quando a expedicao foi rejeitada', function () {
    $conselheiro = Usuario::factory()->conselho()->create(['nome' => 'Elrond']);
    $expedicao = Expedicao::factory()
        ->completa()
        ->rejeitada('Rota sob domínio inimigo.', $conselheiro)
        ->create();

    $this->getJson(route('v1.expedicoes.show', $expedicao->protocolo))
        ->assertOk()
        ->assertJsonPath('status', StatusExpedicao::Rejeitada->value)
        ->assertJsonPath('motivo_rejeicao', 'Rota sob domínio inimigo.')
        ->assertJsonPath('decidido_por.nome', 'Elrond')
        ->assertJsonPath('decidido_em', $expedicao->decidido_em?->toIso8601String());
});

it('mostra decidido_por e motivo como null enquanto a expedicao esta pendente', function () {
    $expedicao = Expedicao::factory()->completa()->create();

    $this->getJson(route('v1.expedicoes.show', $expedicao->protocolo))
        ->assertOk()
        ->assertJsonPath('motivo_rejeicao', null)
        ->assertJsonPath('decidido_por', null)
        ->assertJsonPath('decidido_em', null);
});

it('nao expoe email nem o estado da conta do reino na consulta publica', function () {
    $reino = Usuario::factory()->reino()->create(['email' => 'gondor@reinos.elrond.test']);
    $expedicao = Expedicao::factory()->for($reino, 'reino')->completa()->create();

    $resposta = $this->getJson(route('v1.expedicoes.show', $expedicao->protocolo));

    expect($resposta->json('reino'))->not->toHaveKey('email')
        ->and($resposta->json('reino'))->not->toHaveKey('ativo')
        ->and($resposta->content())->not->toContain('gondor@reinos.elrond.test');
});

it('consulta a expedicao sem disparar consultas N+1', function () {
    $consultasPara = function (int $filhos): int {
        $expedicao = Expedicao::factory()->completa(participantes: $filhos, itens: $filhos)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('v1.expedicoes.show', $expedicao->protocolo))->assertOk();

        return count(DB::getQueryLog());
    };

    // O custo da consulta não pode crescer com o tamanho da expedição.
    expect($consultasPara(2))->toBe($consultasPara(20));
});
