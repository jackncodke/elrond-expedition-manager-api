<?php

use App\Models\Expedicao;
use Illuminate\Support\Facades\Route;

it('publica todos os endpoints da api sob o prefixo de versao /api/v1', function () {
    $semVersao = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($rota): string => '/'.ltrim($rota->uri(), '/'))
        ->filter(fn (string $uri): bool => str_starts_with($uri, '/api/'))
        // O Swagger UI e ferramenta de leitura do contrato, nao faz parte dele.
        ->reject(fn (string $uri): bool => $uri === '/api/documentation')
        ->reject(fn (string $uri): bool => str_starts_with($uri, '/api/v1/'))
        ->values();

    expect($semVersao->all())->toBeEmpty();
});

it('prefixa com v1 o nome das rotas da versao 1', function () {
    expect(route('v1.login', absolute: false))->toBe('/api/v1/login')
        ->and(route('v1.logout', absolute: false))->toBe('/api/v1/logout')
        ->and(route('v1.expedicoes.store', absolute: false))->toBe('/api/v1/expedicoes')
        ->and(route('v1.expedicoes.show', 'ELR-2026-000001', absolute: false))->toBe('/api/v1/expedicoes/ELR-2026-000001')
        ->and(route('v1.expedicoes.status', 'ELR-2026-000001', absolute: false))->toBe('/api/v1/expedicoes/ELR-2026-000001/status')
        ->and(route('v1.expedicoes.decisao', 'ELR-2026-000001', absolute: false))->toBe('/api/v1/expedicoes/ELR-2026-000001/decisao');
});

it('nao responde mais pelos caminhos sem versao', function () {
    $expedicao = Expedicao::factory()->create();

    $this->getJson("/api/expedicoes/{$expedicao->protocolo}")->assertNotFound();
    $this->postJson('/api/login')->assertNotFound();
});

it('responde 401 em json a um visitante sem token mesmo sem pedir json', function () {
    $this->post(route('v1.logout'))
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthenticated.');
});
