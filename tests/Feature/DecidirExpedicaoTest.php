<?php

use App\Enums\StatusExpedicao;
use App\Http\Middleware\EnsureRequestIsInternal;
use App\Models\Expedicao;
use App\Models\Usuario;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->expedicao = Expedicao::factory()->completa()->create();
    $this->conselheiro = Usuario::factory()->conselho()->create();
});

/**
 * Requisição com as três camadas satisfeitas: IP interno, token de serviço e
 * usuário do Conselho. Cada teste derruba só a camada que quer exercitar.
 */
function decidir(array $corpo, array $opcoes = [])
{
    $teste = test();

    // array_key_exists, e não ??, para que 'usuario' => null signifique
    // "sem autenticação" em vez de cair no conselheiro padrão.
    $usuario = array_key_exists('usuario', $opcoes) ? $opcoes['usuario'] : $teste->conselheiro;
    $ip = $opcoes['ip'] ?? '127.0.0.1';
    $protocolo = $opcoes['protocolo'] ?? $teste->expedicao->protocolo;

    $headers = array_key_exists('token', $opcoes)
        ? ($opcoes['token'] === null ? [] : [EnsureRequestIsInternal::HEADER_TOKEN => $opcoes['token']])
        : [EnsureRequestIsInternal::HEADER_TOKEN => config('conselho.interno.token_de_servico')];

    $requisicao = $teste->withServerVariables(['REMOTE_ADDR' => $ip]);

    if ($usuario !== null) {
        $requisicao = $requisicao->actingAs($usuario, 'sanctum');
    }

    return $requisicao->patchJson(route('expedicoes.decisao', $protocolo), $corpo, $headers);
}

it('autoriza a expedicao quando as tres camadas sao validas', function () {
    decidir(['decisao' => StatusExpedicao::Autorizada->value])
        ->assertOk()
        ->assertJsonPath('status', StatusExpedicao::Autorizada->value)
        ->assertJsonPath('motivo_rejeicao', null)
        ->assertJsonPath('decidido_por.id', $this->conselheiro->id);

    $this->expedicao->refresh();

    expect($this->expedicao->status)->toBe(StatusExpedicao::Autorizada)
        ->and($this->expedicao->decidido_por)->toBe($this->conselheiro->id)
        ->and($this->expedicao->decidido_em)->not->toBeNull();
});

it('rejeita a expedicao com motivo', function () {
    decidir([
        'decisao' => StatusExpedicao::Rejeitada->value,
        'motivo' => 'Rota sob domínio inimigo.',
    ])
        ->assertOk()
        ->assertJsonPath('status', StatusExpedicao::Rejeitada->value)
        ->assertJsonPath('motivo_rejeicao', 'Rota sob domínio inimigo.');

    expect($this->expedicao->refresh()->motivo_rejeicao)->toBe('Rota sob domínio inimigo.');
});

it('recusa rejeitar sem motivo', function () {
    decidir(['decisao' => StatusExpedicao::Rejeitada->value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('motivo');

    expect($this->expedicao->refresh()->status)->toBe(StatusExpedicao::Pendente);
});

it('recusa rejeitar com motivo em branco', function () {
    decidir(['decisao' => StatusExpedicao::Rejeitada->value, 'motivo' => '   '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('motivo');
});

it('nao grava motivo numa expedicao autorizada', function () {
    decidir([
        'decisao' => StatusExpedicao::Autorizada->value,
        'motivo' => 'Não deveria ser gravado.',
    ])->assertOk()->assertJsonPath('motivo_rejeicao', null);

    expect($this->expedicao->refresh()->motivo_rejeicao)->toBeNull();
});

it('exige uma decisao valida', function () {
    decidir([])->assertUnprocessable()->assertJsonValidationErrors('decisao');
    decidir(['decisao' => 'TALVEZ'])->assertUnprocessable()->assertJsonValidationErrors('decisao');
    decidir(['decisao' => StatusExpedicao::Pendente->value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('decisao');
});

it('bloqueia com 403 quando o IP nao e confiavel', function () {
    decidir(['decisao' => StatusExpedicao::Autorizada->value], ['ip' => '203.0.113.9'])
        ->assertForbidden();

    expect($this->expedicao->refresh()->status)->toBe(StatusExpedicao::Pendente);
});

it('bloqueia com 403 quando o token de servico esta ausente', function () {
    decidir(['decisao' => StatusExpedicao::Autorizada->value], ['token' => null])
        ->assertForbidden();

    expect($this->expedicao->refresh()->status)->toBe(StatusExpedicao::Pendente);
});

it('bloqueia com 403 quando o token de servico e invalido', function () {
    decidir(['decisao' => StatusExpedicao::Autorizada->value], ['token' => 'token-errado'])
        ->assertForbidden();
});

it('bloqueia com 403 um usuario do tipo REINO tentando decidir', function () {
    decidir(
        ['decisao' => StatusExpedicao::Autorizada->value],
        ['usuario' => Usuario::factory()->reino()->create()],
    )->assertForbidden();

    expect($this->expedicao->refresh()->status)->toBe(StatusExpedicao::Pendente);
});

it('responde 403 e nao 401 quando a requisicao vem de fora sem autenticacao', function () {
    decidir(
        ['decisao' => StatusExpedicao::Autorizada->value],
        ['usuario' => null, 'ip' => '203.0.113.9'],
    )->assertForbidden();
});

it('nao serve de oraculo de token para quem esta fora da rede', function () {
    // De fora, um token de usuário inválido e um válido precisam produzir a
    // mesma resposta: senão dá para testar tokens contra o endpoint interno.
    $comTokenInvalido = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->withHeader('Authorization', 'Bearer token-inventado')
        ->patchJson(route('expedicoes.decisao', $this->expedicao->protocolo), [
            'decisao' => StatusExpedicao::Autorizada->value,
        ]);

    $comTokenValido = decidir(
        ['decisao' => StatusExpedicao::Autorizada->value],
        ['ip' => '203.0.113.9'],
    );

    $comTokenInvalido->assertForbidden();
    $comTokenValido->assertForbidden();
    expect($comTokenInvalido->json('message'))->toBe($comTokenValido->json('message'));
});

it('responde 401 quando a requisicao e interna mas nao ha usuario autenticado', function () {
    decidir(['decisao' => StatusExpedicao::Autorizada->value], ['usuario' => null])
        ->assertUnauthorized();
});

it('recusa com 409 decidir uma expedicao ja autorizada', function () {
    decidir(['decisao' => StatusExpedicao::Autorizada->value])->assertOk();

    decidir(['decisao' => StatusExpedicao::Rejeitada->value, 'motivo' => 'Mudei de ideia.'])
        ->assertStatus(Response::HTTP_CONFLICT)
        ->assertJsonPath('status', StatusExpedicao::Autorizada->value);

    expect($this->expedicao->refresh()->status)->toBe(StatusExpedicao::Autorizada);
});

it('recusa com 409 decidir uma expedicao ja rejeitada', function () {
    decidir(['decisao' => StatusExpedicao::Rejeitada->value, 'motivo' => 'Perigosa demais.'])->assertOk();

    decidir(['decisao' => StatusExpedicao::Autorizada->value])
        ->assertStatus(Response::HTTP_CONFLICT);

    expect($this->expedicao->refresh()->motivo_rejeicao)->toBe('Perigosa demais.');
});

it('devolve 404 para um protocolo inexistente', function () {
    decidir(
        ['decisao' => StatusExpedicao::Autorizada->value],
        ['protocolo' => 'ELR-2026-999999'],
    )->assertNotFound();
});

it('registra qual membro do conselho decidiu', function () {
    $outro = Usuario::factory()->conselho()->create(['nome' => 'Galadriel']);

    decidir(['decisao' => StatusExpedicao::Autorizada->value], ['usuario' => $outro])
        ->assertOk()
        ->assertJsonPath('decidido_por.nome', 'Galadriel');

    expect($outro->expedicoesDecididas()->count())->toBe(1);
});
