<?php

use App\Http\Controllers\DecisaoExpedicaoController;
use App\Http\Controllers\ExpedicaoController;
use App\Http\Controllers\LoginController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

$expedicaoNaoEncontrada = fn () => response()->json(
    ['message' => 'Nenhuma expedição encontrada para o protocolo informado.'],
    Response::HTTP_NOT_FOUND,
);

Route::post('/login', LoginController::class)
    ->middleware('throttle:login')
    ->name('login');

/**
 * Consulta pública: qualquer um que tenha o protocolo acompanha a expedição.
 * O `missing()` troca o 404 padrão do route model binding, que cita o nome da
 * classe do model, por uma mensagem do domínio.
 */
Route::get('/expedicoes/{expedicao}', [ExpedicaoController::class, 'show'])
    ->middleware('throttle:consulta-publica')
    ->missing($expedicaoNaoEncontrada)
    ->name('expedicoes.show');

Route::middleware(['auth:sanctum', 'reino'])->group(function (): void {
    Route::post('/expedicoes', [ExpedicaoController::class, 'store'])->name('expedicoes.store');
});

/**
 * Rota interna. A ordem dos middlewares importa: `interno` roda primeiro, então
 * uma requisição de fora recebe o 403 genérico sem que a API revele se o token
 * de usuário era válido.
 */
Route::middleware(['interno', 'auth:sanctum', 'conselho'])->group(function () use ($expedicaoNaoEncontrada): void {
    Route::patch('/expedicoes/{expedicao}/decisao', [DecisaoExpedicaoController::class, 'update'])
        ->missing($expedicaoNaoEncontrada)
        ->name('expedicoes.decisao');
});
