<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControlTowerController;
use App\Http\Controllers\DivisaoController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\ModeloEquipamentoController;
use App\Http\Controllers\SubDivisaoController;
use App\Http\Controllers\TipoEquipamentoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('users.index'));

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('torre-de-controle', [ControlTowerController::class, 'index'])->name('control-tower.index');
    Route::patch('torre-de-controle/{equipamento}/implemento', [ControlTowerController::class, 'updateImplemento'])->name('control-tower.implemento');

    Route::resource('users', UserController::class)->except(['show']);
    Route::get('users-export', [UserController::class, 'export'])->name('users.export');

    Route::resource('divisoes', DivisaoController::class)->except(['show'])->parameters(['divisoes' => 'divisao']);
    Route::get('divisoes-export', [DivisaoController::class, 'export'])->name('divisoes.export');
    Route::resource('subdivisoes', SubDivisaoController::class)->except(['show'])->parameters(['subdivisoes' => 'subDivisao']);
    Route::get('subdivisoes-export', [SubDivisaoController::class, 'export'])->name('subdivisoes.export');
    Route::resource('tipos-equipamentos', TipoEquipamentoController::class)->except(['show'])->parameters(['tipos-equipamentos' => 'tipoEquipamento']);
    Route::get('tipos-equipamentos-export', [TipoEquipamentoController::class, 'export'])->name('tipos-equipamentos.export');
    Route::resource('modelos-equipamentos', ModeloEquipamentoController::class)->except(['show'])->parameters(['modelos-equipamentos' => 'modeloEquipamento']);
    Route::get('modelos-equipamentos-export', [ModeloEquipamentoController::class, 'export'])->name('modelos-equipamentos.export');
    Route::resource('equipamentos', EquipamentoController::class)->except(['show'])->parameters(['equipamentos' => 'equipamento']);
    Route::get('equipamentos-export', [EquipamentoController::class, 'export'])->name('equipamentos.export');
    Route::patch('equipamentos/{equipamento}/operacional', [EquipamentoController::class, 'updateOperacional'])->name('equipamentos.operacional');
});
