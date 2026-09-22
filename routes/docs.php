<?php

use App\Http\Controllers\EspecificacaoOpenApiController;
use Illuminate\Support\Facades\Route;

/**
 * Rotas de documentação servidas fora do prefixo `/api`.
 *
 * Registradas pelo `then:` do `bootstrap/app.php`, e não como rotas `web`: o
 * projeto não tem sessão, cookies nem CSRF, e nada disso faz falta para servir
 * um arquivo estático.
 */
Route::get('/docs/openapi.json', EspecificacaoOpenApiController::class)
    ->middleware('throttle:consulta-publica')
    ->name('docs.openapi');
