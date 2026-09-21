<?php

use App\Http\Controllers\ExpedicaoController;
use App\Http\Controllers\LoginController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

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
    ->missing(fn () => response()->json(
        ['message' => 'Nenhuma expedição encontrada para o protocolo informado.'],
        Response::HTTP_NOT_FOUND,
    ))
    ->name('expedicoes.show');

Route::middleware(['auth:sanctum', 'reino'])->group(function (): void {
    Route::post('/expedicoes', [ExpedicaoController::class, 'store'])->name('expedicoes.store');
});
