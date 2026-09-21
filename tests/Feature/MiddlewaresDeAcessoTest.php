<?php

use App\Http\Middleware\EnsureRequestIsInternal;
use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

/**
 * Os middlewares são exercitados sobre rotas declaradas aqui, e não sobre os
 * endpoints reais, para que estes testes continuem cobrindo apenas a camada de
 * acesso mesmo quando as regras de negócio dos endpoints mudarem.
 */
beforeEach(function () {
    Route::middleware(['auth:sanctum', 'reino'])->get('/_teste/reino', fn (): array => ['ok' => true]);
    Route::middleware(['auth:sanctum', 'conselho'])->get('/_teste/conselho', fn (): array => ['ok' => true]);
    Route::middleware('interno')->get('/_teste/interno', fn (): array => ['ok' => true]);
});

function headerInterno(?string $token = null): array
{
    return [EnsureRequestIsInternal::HEADER_TOKEN => $token ?? config('conselho.interno.token_de_servico')];
}

it('deixa passar um usuario REINO na rota de reino', function () {
    $this->actingAs(Usuario::factory()->reino()->create(), 'sanctum')
        ->getJson('/_teste/reino')
        ->assertOk();
});

it('bloqueia um usuario CONSELHO na rota de reino', function () {
    $this->actingAs(Usuario::factory()->conselho()->create(), 'sanctum')
        ->getJson('/_teste/reino')
        ->assertForbidden();
});

it('deixa passar um usuario CONSELHO na rota de conselho', function () {
    $this->actingAs(Usuario::factory()->conselho()->create(), 'sanctum')
        ->getJson('/_teste/conselho')
        ->assertOk();
});

it('bloqueia um usuario REINO na rota de conselho', function () {
    $this->actingAs(Usuario::factory()->reino()->create(), 'sanctum')
        ->getJson('/_teste/conselho')
        ->assertForbidden();
});

it('responde 401 quando nao ha usuario autenticado', function () {
    $this->getJson('/_teste/reino')->assertUnauthorized();
    $this->getJson('/_teste/conselho')->assertUnauthorized();
});

it('aceita a requisicao interna com IP na allowlist e token valido', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('/_teste/interno', headerInterno())
        ->assertOk();
});

it('aceita um IP dentro de uma faixa CIDR da allowlist', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '10.14.200.7'])
        ->getJson('/_teste/interno', headerInterno())
        ->assertOk();
});

it('bloqueia um IP fora da allowlist mesmo com token valido', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->getJson('/_teste/interno', headerInterno())
        ->assertForbidden();
});

it('bloqueia um IP logo fora da faixa CIDR', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '11.0.0.1'])
        ->getJson('/_teste/interno', headerInterno())
        ->assertForbidden();
});

it('bloqueia quando o token de servico esta ausente', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('/_teste/interno')
        ->assertForbidden();
});

it('bloqueia quando o token de servico esta errado', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('/_teste/interno', headerInterno('token-errado'))
        ->assertForbidden();
});

it('bloqueia quando nenhum token de servico foi configurado', function () {
    config(['conselho.interno.token_de_servico' => null]);

    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('/_teste/interno', headerInterno('qualquer-coisa'))
        ->assertForbidden();
});

it('bloqueia quando a allowlist de IPs esta vazia', function () {
    config(['conselho.interno.ips_permitidos' => []]);

    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('/_teste/interno', headerInterno())
        ->assertForbidden();
});

it('devolve a mesma mensagem generica seja qual for a camada que barrou', function () {
    $porIp = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->getJson('/_teste/interno', headerInterno());

    $porToken = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->getJson('/_teste/interno', headerInterno('token-errado'));

    expect($porIp->json('message'))->toBe($porToken->json('message'))
        ->and($porIp->json('message'))->not->toContain('IP')
        ->and($porIp->json('message'))->not->toContain('token');
});
