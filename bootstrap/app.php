<?php

use App\Http\Middleware\EnsureRequestIsInternal;
use App\Http\Middleware\EnsureUserIsConselho;
use App\Http\Middleware\EnsureUserIsReino;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'reino' => EnsureUserIsReino::class,
            'conselho' => EnsureUserIsConselho::class,
            'interno' => EnsureRequestIsInternal::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
    })->create();
