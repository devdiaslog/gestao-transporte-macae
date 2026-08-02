<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DemandaConsultaController;
use App\Http\Controllers\Api\DemandaImportacaoController;
use App\Http\Controllers\Api\ItemLiberadoImportacaoController;
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

    // Consulta dos atendimentos, para a automação buscar no SAP só o que precisa.
    Route::get('/demandas', [DemandaConsultaController::class, 'index'])->name('api.demandas.index');

    Route::post('/demandas/importar', [DemandaImportacaoController::class, 'store'])->name('api.demandas.importar');

    // Itens liberados pelo cliente no SAP (status 03), ainda sem atendimento.
    Route::post('/itens-liberados/importar', [ItemLiberadoImportacaoController::class, 'store'])
        ->name('api.itens-liberados.importar');
});
