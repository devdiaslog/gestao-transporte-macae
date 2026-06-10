<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BigcoreController;
use App\Http\Controllers\CercaController;
use App\Http\Controllers\ControlTowerController;
use App\Http\Controllers\DivisaoController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\JustificativaController;
use App\Http\Controllers\MetricasController;
use App\Http\Controllers\ModeloEquipamentoController;
use App\Http\Controllers\MotoristaController;
use App\Http\Controllers\OcorrenciaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResponsavelController;
use App\Http\Controllers\SubDivisaoController;
use App\Http\Controllers\TipoEquipamentoController;
use App\Http\Controllers\TipoOcorrenciaController;
use App\Http\Controllers\UserController;
use App\Services\BigcoreService;
use App\Services\StatusOperacionalService;
use App\Services\VfleetsService;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('ocorrencias.index'));

// Sincronização de posições — protegida por chave secreta
Route::get('sync/posicoes', function (VfleetsService $vfleets) {
    if (request('key') !== config('services.vfleets.sync_key') || ! config('services.vfleets.sync_key')) {
        abort(403);
    }

    try {
        $total = $vfleets->sincronizar();

        return response()->json(['ok' => true, 'sincronizados' => $total]);
    } catch (Throwable $e) {
        return response()->json(['ok' => false, 'erro' => $e->getMessage()], 500);
    }
})->name('sync.posicoes');

// Sincronização de status operacional via Bigcore — protegida por chave secreta
Route::get('sync/status-operacional', function (BigcoreService $bigcore, StatusOperacionalService $service) {
    if (request('key') !== config('services.bigcore.sync_key') || ! config('services.bigcore.sync_key')) {
        abort(403);
    }

    try {
        $registros = $bigcore->buscarTodos();
        $total = $service->sincronizar($registros, now());

        return response()->json(['ok' => true, 'processados' => count($registros), 'alteracoes' => $total]);
    } catch (Throwable $e) {
        return response()->json(['ok' => false, 'erro' => $e->getMessage()], 500);
    }
})->name('sync.status-operacional');

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware('auth')->group(function () {
    // Torre de Controle — acessível a todos os perfis
    Route::get('torre-de-controle', [ControlTowerController::class, 'index'])->name('control-tower.index');
    Route::get('torre-de-controle/painel', [ControlTowerController::class, 'painel'])->name('control-tower.painel');
    Route::get('torre-de-controle-export', [ControlTowerController::class, 'export'])->name('control-tower.export');
    Route::get('torre-de-controle/mapa-geral', [ControlTowerController::class, 'mapaGeral'])->name('control-tower.mapa-geral');
    Route::post('torre-de-controle/sincronizar-posicoes', [ControlTowerController::class, 'sincronizarPosicoes'])->name('control-tower.sincronizar-posicoes');
    Route::post('torre-de-controle/sincronizar-status-operacional', [ControlTowerController::class, 'sincronizarStatusOperacional'])->name('control-tower.sincronizar-status-operacional');
    Route::get('torre-de-controle/posicao/{plate}', [ControlTowerController::class, 'posicao'])->name('control-tower.posicao');
    Route::post('torre-de-controle/{equipamento}/reporte-rapido', [ControlTowerController::class, 'reporteRapido'])->name('control-tower.reporte-rapido');
    Route::patch('torre-de-controle/{equipamento}/implemento', [ControlTowerController::class, 'updateImplemento'])->name('control-tower.implemento');
    Route::get('torre-de-controle/{equipamento}/historico', [ControlTowerController::class, 'historico'])->name('control-tower.historico');

    // Ocorrências — acessível a todos os perfis (restrições de edição/exclusão tratadas no controller)
    Route::get('ocorrencias-export', [OcorrenciaController::class, 'export'])->name('ocorrencias.export');
    Route::get('ocorrencias/veiculo/{equipamento}', [OcorrenciaController::class, 'veiculo'])->name('ocorrencias.veiculo');
    Route::patch('ocorrencias/{ocorrencia}/auditoria', [OcorrenciaController::class, 'auditar'])->name('ocorrencias.auditar');
    Route::resource('ocorrencias', OcorrenciaController::class)->except(['show'])->parameters(['ocorrencias' => 'ocorrencia']);

    // Bigcore (Elog)
    Route::get('bigcore/veiculo', [BigcoreController::class, 'veiculo'])->name('bigcore.veiculo');

    // Métricas
    Route::get('metricas/{periodo?}', [MetricasController::class, 'index'])
        ->where('periodo', 'hoje|7|30|90')
        ->name('metricas.index');

    // Reportes
    Route::get('reportes/ultimo-por-prefixo', [ReporteController::class, 'ultimoPorPrefixo'])->name('reportes.ultimo-por-prefixo');
    Route::get('reportes/{reporte}/data', [ReporteController::class, 'data'])->name('reportes.data');
    Route::resource('reportes', ReporteController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->parameters(['reportes' => 'reporte']);

    // Usuários — apenas Administrador
    Route::middleware('can:manage-users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('users-export', [UserController::class, 'export'])->name('users.export');
    });

    // Cadastros — Administrador e Supervisor
    Route::middleware('can:manage-cadastros')->group(function () {
        Route::resource('cercas', CercaController::class)->except(['show'])->parameters(['cercas' => 'cerca']);
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
        Route::resource('motoristas', MotoristaController::class)->except(['show']);
    });

    // Tabelas de apoio de ocorrências — Administrador e Supervisor
    Route::middleware('can:manage-support-tables')->group(function () {
        Route::resource('responsaveis', ResponsavelController::class)->except(['show'])->parameters(['responsaveis' => 'responsavel']);
        Route::resource('tipos-ocorrencia', TipoOcorrenciaController::class)->except(['show'])->parameters(['tipos-ocorrencia' => 'tipoOcorrencia']);
        Route::resource('justificativas', JustificativaController::class)->except(['show'])->parameters(['justificativas' => 'justificativa']);
    });
});
