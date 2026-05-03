<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OcorrenciaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Autenticação
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Rotas protegidas por token Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    Route::get('/ocorrencias', [OcorrenciaController::class, 'index'])->name('api.ocorrencias.index');
});
