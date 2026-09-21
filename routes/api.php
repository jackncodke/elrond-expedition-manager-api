<?php

use App\Http\Controllers\ExpedicaoController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class)
    ->middleware('throttle:login')
    ->name('login');

Route::middleware(['auth:sanctum', 'reino'])->group(function (): void {
    Route::post('/expedicoes', [ExpedicaoController::class, 'store'])->name('expedicoes.store');
});
