<?php

use App\Http\Controllers\DecisaoExpedicaoController;
use App\Http\Controllers\DocumentacaoController;
use App\Http\Controllers\ExpedicaoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\StatusExpedicaoController;
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
 * Encerra a sessão atual. Vale para os dois tipos de usuário, por isso só
 * exige o guard do Sanctum, sem o middleware de tipo.
 */
Route::post('/logout', LogoutController::class)
    ->middleware('auth:sanctum')
    ->name('logout');

/**
 * Swagger UI, público e sem autenticação. Lê a especificação servida por
 * `GET /docs/openapi.json` (ver routes/docs.php).
 */
Route::get('/documentation', DocumentacaoController::class)
    ->middleware('throttle:consulta-publica')
    ->name('documentation');

/**
 * Consulta pública: qualquer um que tenha o protocolo acompanha a expedição.
 * O `missing()` troca o 404 padrão do route model binding, que cita o nome da
 * classe do model, por uma mensagem do domínio.
 */
Route::middleware('throttle:consulta-publica')->group(function () use ($expedicaoNaoEncontrada): void {
    Route::get('/expedicoes/{expedicao}', [ExpedicaoController::class, 'show'])
        ->missing($expedicaoNaoEncontrada)
        ->name('expedicoes.show');

    Route::get('/expedicoes/{expedicao}/status', [StatusExpedicaoController::class, 'show'])
        ->missing($expedicaoNaoEncontrada)
        ->name('expedicoes.status');
});

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
