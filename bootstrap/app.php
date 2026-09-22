<?php

use App\Http\Middleware\EnsureRequestIsInternal;
use App\Http\Middleware\EnsureUserIsConselho;
use App\Http\Middleware\EnsureUserIsReino;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // As rotas de documentação vivem fora do prefixo `/api` e não precisam
        // do grupo `web`, que traria sessão, cookies e CSRF para um projeto que
        // não usa nenhum dos três.
        then: function (): void {
            Route::group([], __DIR__.'/../routes/docs.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'reino' => EnsureUserIsReino::class,
            'conselho' => EnsureUserIsConselho::class,
            'interno' => EnsureRequestIsInternal::class,
        ]);

        // A ordem declarada na rota não basta: o Laravel reordena os
        // middlewares pela lista de prioridade, onde a autenticação já entra.
        // Sem isto, uma requisição externa com token inválido receberia 401 do
        // Sanctum antes de o EnsureRequestIsInternal barrar — virando um
        // oráculo de validade de token para quem está fora da rede.
        //
        // O alvo é a interface AuthenticatesRequests, e não a classe
        // Authenticate: é ela que consta na lista de prioridade padrão.
        // Projeto API-only: visitante sem token recebe 401 em JSON, nunca um
        // redirecionamento. O padrão do framework aponta para `route('login')`,
        // que não existe mais depois que as rotas ganharam prefixo de versão.
        $middleware->redirectGuestsTo(null);

        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnsureRequestIsInternal::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
    })->create();
