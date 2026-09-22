<?php

use Illuminate\Support\Facades\Route;

it('serve o Swagger UI sem exigir autenticacao', function () {
    $resposta = $this->get(route('documentation'));

    $resposta->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=utf-8');

    expect($resposta->getContent())
        ->toContain('<div id="swagger-ui">')
        ->toContain('SwaggerUIBundle');
});

it('aponta o Swagger UI para a rota da especificacao', function () {
    $conteudo = $this->get(route('documentation'))->getContent();

    // A URL entra no JavaScript como literal de string, via json_encode.
    expect($conteudo)->toContain('url: "'.route('docs.openapi').'"');
});

it('fixa a versao do Swagger UI e exige integridade dos arquivos do CDN', function () {
    $conteudo = $this->get(route('documentation'))->getContent();

    expect($conteudo)
        ->toContain('https://unpkg.com/swagger-ui-dist@5.33.0/swagger-ui.css')
        ->toContain('https://unpkg.com/swagger-ui-dist@5.33.0/swagger-ui-bundle.js')
        // Sem versao fixa o CDN passaria a servir o que quisesse; sem SRI, o
        // navegador rodaria o que viesse de la sem conferir.
        ->not->toContain('swagger-ui-dist/')
        ->toContain('integrity="sha384-')
        ->toContain('crossorigin="anonymous"');

    expect(substr_count($conteudo, 'integrity="sha384-'))->toBe(2);
});

it('serve a especificacao OpenAPI sem exigir autenticacao', function () {
    $resposta = $this->get(route('docs.openapi'));

    $resposta->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=utf-8');

    expect($resposta->json('openapi'))->toBe('3.0.3')
        ->and($resposta->json('info.title'))->toBe('API do Conselho de Elrond');
});

it('serve a especificacao fora do prefixo /api', function () {
    expect(route('docs.openapi', absolute: false))->toBe('/docs/openapi.json');
    expect(route('documentation', absolute: false))->toBe('/api/documentation');
});

it('entrega a especificacao byte a byte igual ao arquivo versionado', function () {
    $resposta = $this->get(route('docs.openapi'));

    expect($resposta->getContent())
        ->toBe(file_get_contents(config('conselho.documentacao.especificacao')));
});

it('cobre na especificacao todas as rotas publicadas da api', function () {
    $documentadas = array_keys($this->get(route('docs.openapi'))->json('paths'));

    $registradas = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($rota): string => '/'.ltrim($rota->uri(), '/'))
        ->filter(fn (string $uri): bool => str_starts_with($uri, '/api/'))
        // O proprio Swagger UI nao faz parte do contrato da API.
        ->reject(fn (string $uri): bool => $uri === '/api/documentation')
        ->map(fn (string $uri): string => str_replace('{expedicao}', '{protocolo}', $uri))
        ->unique()
        ->values();

    expect($registradas->diff($documentadas)->all())->toBeEmpty();
});

it('devolve 404 quando a especificacao nao esta no lugar', function () {
    config()->set('conselho.documentacao.especificacao', base_path('docs/nao-existe.json'));

    $this->getJson(route('docs.openapi'))
        ->assertNotFound()
        ->assertJsonPath('message', 'Especificação OpenAPI não encontrada.');
});
