<?php

use App\Http\Controllers\DocumentacaoController;
use Illuminate\Support\Facades\Route;

/**
 * Ponto de entrada das rotas sob `/api`.
 *
 * Cada versão do contrato vive no seu próprio arquivo em `routes/api/`, com
 * prefixo de URL (`/api/v1`) e de nome de rota (`v1.`) próprios. Uma versão
 * nova entra como mais um grupo aqui, ao lado das anteriores, sem mexer nelas.
 */
Route::prefix('v1')
    ->name('v1.')
    ->group(base_path('routes/api/v1.php'));

/**
 * Swagger UI, público e sem autenticação. Fica fora das versões porque é
 * ferramenta de leitura do contrato, não parte dele. Lê a especificação servida
 * por `GET /docs/openapi.json` (ver routes/docs.php).
 */
Route::get('/documentation', DocumentacaoController::class)
    ->middleware('throttle:consulta-publica')
    ->name('documentation');
