<?php

use App\Enums\StatusExpedicao;
use App\Models\Expedicao;
use App\Models\Usuario;
use App\Support\GeradorDeProtocolo;

/**
 * Data de partida relativa, e não fixa: uma data fixa vira passado com o tempo
 * e passaria a falhar na regra `after_or_equal:today`.
 */
function dataDePartida(string $quando = '+6 weeks'): string
{
    return now()->modify($quando)->toDateString();
}

function payloadDaExpedicao(array $sobrescrever = []): array
{
    return [...[
        'jornada' => [
            'origem' => 'Minas Tirith',
            'destino' => 'Osgiliath',
            'data_partida_prevista' => dataDePartida(),
            'rota_descricao' => 'Travessia do Anduin ao amanhecer.',
        ],
        'participantes' => [
            ['nome' => 'Faramir', 'raca' => 'Humano', 'papel' => 'Líder'],
            ['nome' => 'Beregond', 'raca' => 'Humano', 'papel' => 'Guarda'],
        ],
        'itens_suprimento' => [
            ['nome' => 'Arco de Ithilien', 'tipo' => 'arma', 'quantidade' => 12, 'observacoes' => 'Com aljavas.'],
            ['nome' => 'Lembas', 'tipo' => 'provisao', 'quantidade' => 40, 'observacoes' => null],
        ],
    ], ...$sobrescrever];
}

function criarComoReino(?array $payload = null, ?Usuario $reino = null)
{
    return test()
        ->actingAs($reino ?? Usuario::factory()->reino()->create(), 'sanctum')
        ->postJson(route('expedicoes.store'), $payload ?? payloadDaExpedicao());
}

it('registra a expedicao com jornada, participantes e itens numa unica requisicao', function () {
    $reino = Usuario::factory()->reino()->create();

    $resposta = criarComoReino(reino: $reino);

    $resposta->assertCreated()
        ->assertJsonPath('status', StatusExpedicao::Pendente->value)
        ->assertJsonPath('motivo_rejeicao', null)
        ->assertJsonPath('decidido_por', null)
        ->assertJsonPath('decidido_em', null)
        ->assertJsonPath('jornada.origem', 'Minas Tirith')
        ->assertJsonPath('jornada.data_partida_prevista', dataDePartida())
        ->assertJsonCount(2, 'participantes')
        ->assertJsonCount(2, 'itens_suprimento')
        ->assertJsonPath('participantes.0.nome', 'Faramir')
        ->assertJsonPath('itens_suprimento.0.quantidade', 12);

    $expedicao = Expedicao::sole();

    expect($resposta->json('protocolo'))->toBe($expedicao->protocolo)
        ->and($expedicao->usuario_id)->toBe($reino->id);

    $this->assertDatabaseCount('jornadas', 1);
    $this->assertDatabaseCount('participantes', 2);
    $this->assertDatabaseCount('itens_suprimento', 2);
});

it('devolve o protocolo gerado com status 201', function () {
    $resposta = criarComoReino();

    $resposta->assertCreated();
    expect($resposta->json('protocolo'))->toMatch('/^ELR-\d{4}-\d{6}$/');
});

it('gera protocolos unicos e sequenciais', function () {
    $reino = Usuario::factory()->reino()->create();

    $primeiro = criarComoReino(reino: $reino)->json('protocolo');
    $segundo = criarComoReino(reino: $reino)->json('protocolo');
    $terceiro = criarComoReino(reino: $reino)->json('protocolo');

    $ano = now()->year;

    expect([$primeiro, $segundo, $terceiro])->toBe([
        "ELR-{$ano}-000001",
        "ELR-{$ano}-000002",
        "ELR-{$ano}-000003",
    ]);
});

it('continua a sequencia a partir das expedicoes ja existentes', function () {
    $ano = now()->year;
    Expedicao::factory()->create(['protocolo' => "ELR-{$ano}-000041"]);

    expect(criarComoReino()->json('protocolo'))->toBe("ELR-{$ano}-000042");
});

it('reinicia a sequencia a cada ano', function () {
    Expedicao::factory()->create(['protocolo' => 'ELR-2025-000900']);

    expect(app(GeradorDeProtocolo::class)->proximo(2026))->toBe('ELR-2026-000001')
        ->and(app(GeradorDeProtocolo::class)->proximo(2025))->toBe('ELR-2025-000901');
});

it('usa o usuario autenticado como solicitante e ignora um usuario_id forjado', function () {
    $reino = Usuario::factory()->reino()->create();
    $outroReino = Usuario::factory()->reino()->create();

    test()->actingAs($reino, 'sanctum')
        ->postJson(route('expedicoes.store'), payloadDaExpedicao(['usuario_id' => $outroReino->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('usuario_id');

    $this->assertDatabaseCount('expedicoes', 0);
});

it('recusa protocolo e status enviados pelo cliente', function () {
    $reino = Usuario::factory()->reino()->create();

    test()->actingAs($reino, 'sanctum')
        ->postJson(route('expedicoes.store'), payloadDaExpedicao([
            'protocolo' => 'ELR-1999-000001',
            'status' => StatusExpedicao::Autorizada->value,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['protocolo', 'status']);
});

it('bloqueia um usuario do CONSELHO', function () {
    test()->actingAs(Usuario::factory()->conselho()->create(), 'sanctum')
        ->postJson(route('expedicoes.store'), payloadDaExpedicao())
        ->assertForbidden();

    $this->assertDatabaseCount('expedicoes', 0);
});

it('exige autenticacao', function () {
    $this->postJson(route('expedicoes.store'), payloadDaExpedicao())->assertUnauthorized();
});

it('exige jornada completa', function () {
    criarComoReino(payloadDaExpedicao(['jornada' => []]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'jornada.origem',
            'jornada.destino',
            'jornada.data_partida_prevista',
        ]);
});

it('recusa uma data de partida invalida', function () {
    criarComoReino(payloadDaExpedicao([
        'jornada' => [
            'origem' => 'Edoras',
            'destino' => 'Isengard',
            'data_partida_prevista' => 'nao-e-data',
        ],
    ]))->assertUnprocessable()->assertJsonValidationErrors('jornada.data_partida_prevista');
});

it('recusa uma data de partida no passado', function () {
    criarComoReino(payloadDaExpedicao([
        'jornada' => [
            'origem' => 'Edoras',
            'destino' => 'Isengard',
            'data_partida_prevista' => dataDePartida('-1 day'),
        ],
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'jornada.data_partida_prevista' => 'A data de partida prevista não pode estar no passado.',
    ]);

    $this->assertDatabaseCount('expedicoes', 0);
});

it('aceita uma expedicao que parte hoje', function () {
    criarComoReino(payloadDaExpedicao([
        'jornada' => [
            'origem' => 'Edoras',
            'destino' => 'Isengard',
            'data_partida_prevista' => dataDePartida('now'),
        ],
    ]))->assertCreated();
});

it('exige ao menos um participante', function () {
    criarComoReino(payloadDaExpedicao(['participantes' => []]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'participantes' => 'A expedição precisa de ao menos um participante.',
        ]);
});

it('valida cada participante individualmente', function () {
    criarComoReino(payloadDaExpedicao([
        'participantes' => [
            ['nome' => 'Faramir', 'raca' => 'Humano', 'papel' => 'Líder'],
            ['nome' => '', 'raca' => str_repeat('a', 61), 'papel' => null],
        ],
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'participantes.1.nome',
        'participantes.1.raca',
        'participantes.1.papel',
    ]);
});

it('valida cada item de suprimento individualmente', function () {
    criarComoReino(payloadDaExpedicao([
        'itens_suprimento' => [
            ['nome' => 'Lembas', 'tipo' => 'provisao', 'quantidade' => 0],
            ['tipo' => 'arma', 'quantidade' => 'muitas'],
        ],
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'itens_suprimento.0.quantidade',
        'itens_suprimento.1.nome',
        'itens_suprimento.1.quantidade',
    ]);
});

it('aceita uma expedicao sem nenhum item de suprimento', function () {
    criarComoReino(payloadDaExpedicao(['itens_suprimento' => []]))
        ->assertCreated()
        ->assertJsonCount(0, 'itens_suprimento');

    $this->assertDatabaseCount('itens_suprimento', 0);
});

it('exige a chave itens_suprimento mesmo que vazia', function () {
    $payload = payloadDaExpedicao();
    unset($payload['itens_suprimento']);

    criarComoReino($payload)->assertUnprocessable()->assertJsonValidationErrors('itens_suprimento');
});

it('nao grava nada quando a validacao falha', function () {
    criarComoReino(payloadDaExpedicao(['participantes' => []]))->assertUnprocessable();

    $this->assertDatabaseCount('expedicoes', 0);
    $this->assertDatabaseCount('jornadas', 0);
    $this->assertDatabaseCount('itens_suprimento', 0);
});
