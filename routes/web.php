<?php

use App\Enums\UserRole;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BigcoreController;
use App\Http\Controllers\CercaController;
use App\Http\Controllers\ControlTowerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandaController;
use App\Http\Controllers\DemandaItemController;
use App\Http\Controllers\DivisaoController;
use App\Http\Controllers\EquipamentoController;
use App\Http\Controllers\JustificativaController;
use App\Http\Controllers\MedicaoController;
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

Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if ($user->role === UserRole::Visualizador) {
        return redirect()->route('mapa-geral.index');
    }

    // Foco do sistema: gestão de demandas. Quem tem acesso ao dashboard
    // entra pela visão gerencial; os demais, pela listagem operacional.
    return redirect()->route($user->can('access-dashboard') ? 'dashboard.demandas' : 'demandas.index');
});

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

// Captura de snapshot do dashboard — protegida por chave secreta
Route::get('dashboard/capturar-status', [DashboardController::class, 'capturarStatus'])->name('dashboard.capturar-status');

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

// Mapa Geral — acessível a todos os perfis, inclusive Visualizador (acesso restrito apenas a isto)
Route::middleware('auth')->group(function () {
    Route::get('mapa-geral', [ControlTowerController::class, 'mapaGeralPagina'])->name('mapa-geral.index');
    Route::get('torre-de-controle/mapa-geral', [ControlTowerController::class, 'mapaGeral'])->name('control-tower.mapa-geral');
    Route::post('torre-de-controle/sincronizar-posicoes', [ControlTowerController::class, 'sincronizarPosicoes'])->name('control-tower.sincronizar-posicoes');
    Route::post('torre-de-controle/sincronizar-status-operacional', [ControlTowerController::class, 'sincronizarStatusOperacional'])->name('control-tower.sincronizar-status-operacional');
});

// Protected routes — todo o restante do sistema, indisponivel ao perfil Visualizador
Route::middleware(['auth', 'can:access-app'])->group(function () {
    // Dashboard de Transporte — acesso por permissão individual
    Route::middleware('can:access-dashboard')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'status'])->name('dashboard.status');
        Route::get('dashboard/graficos', [DashboardController::class, 'graficos'])->name('dashboard.graficos');
        Route::get('dashboard/cards', [DashboardController::class, 'tabela'])->name('dashboard.tabela');
        Route::get('dashboard/indicadores', [DashboardController::class, 'indicadores'])->name('dashboard.indicadores');
        Route::get('dashboard/demandas', [DashboardController::class, 'demandas'])->name('dashboard.demandas');
        Route::post('dashboard/atualizar', [DashboardController::class, 'atualizarManual'])->name('dashboard.atualizar');
    });

    // Demandas de transporte
    Route::get('demandas', [DemandaController::class, 'index'])->name('demandas.index');
    Route::get('demandas-export', [DemandaController::class, 'export'])->name('demandas.export');
    Route::get('demandas-relatorio', [DemandaController::class, 'relatorio'])->name('demandas.relatorio');
    Route::get('demandas-modelo', [DemandaController::class, 'modeloImportacao'])->name('demandas.modelo');
    Route::post('demandas-importar', [DemandaController::class, 'importar'])->name('demandas.importar');
    Route::get('demandas/{demanda}/editar', [DemandaController::class, 'edit'])->name('demandas.edit');
    Route::put('demandas/{demanda}/status-etapa', [DemandaItemController::class, 'atualizarStatusEtapa'])->name('demandas.status-etapa');
    Route::put('demandas/{demanda}/entrega-etapa', [DemandaItemController::class, 'atualizarEntregaEtapa'])->name('demandas.entrega-etapa');
    Route::put('demandas/{demanda}/prazo-etapa', [DemandaItemController::class, 'atualizarPrazoEtapa'])->name('demandas.prazo-etapa');
    Route::post('demandas/{demanda}/itens', [DemandaItemController::class, 'store'])->name('demandas.itens.store');
    Route::post('demandas/{demanda}/itens-importar', [DemandaItemController::class, 'importar'])->name('demandas.itens.importar');
    Route::put('demanda-itens/{item}', [DemandaItemController::class, 'update'])->name('demanda-itens.update')->whereNumber('item');
    Route::delete('demanda-itens/{item}', [DemandaItemController::class, 'destroy'])->name('demanda-itens.destroy')->middleware('can:delete-demanda')->whereNumber('item');
    Route::post('demandas', [DemandaController::class, 'store'])->name('demandas.store');
    Route::put('demandas/{demanda}', [DemandaController::class, 'update'])->name('demandas.update');
    Route::patch('demandas/{demanda}/auditar', [DemandaController::class, 'auditar'])->name('demandas.auditar')->middleware('can:delete-demanda');
    Route::delete('demandas/{demanda}', [DemandaController::class, 'destroy'])->name('demandas.destroy')->middleware('can:delete-demanda');

    // Alertas da frota
    Route::get('alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::get('alertas/pendentes', [AlertaController::class, 'pendentes'])->name('alertas.pendentes');
    Route::post('alertas', [AlertaController::class, 'store'])->name('alertas.store');
    Route::put('alertas/{alerta}', [AlertaController::class, 'update'])->name('alertas.update');
    Route::post('alertas/{alerta}/prorrogar', [AlertaController::class, 'prorrogar'])->name('alertas.prorrogar');
    Route::post('alertas/{alerta}/resolver', [AlertaController::class, 'resolver'])->name('alertas.resolver');
    Route::delete('alertas/{alerta}', [AlertaController::class, 'destroy'])->name('alertas.destroy');

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
        Route::resource('medicoes', MedicaoController::class)->except(['show'])->parameters(['medicoes' => 'medicao']);
    });

    // Tabelas de apoio de ocorrências — Administrador e Supervisor
    Route::middleware('can:manage-support-tables')->group(function () {
        Route::resource('responsaveis', ResponsavelController::class)->except(['show'])->parameters(['responsaveis' => 'responsavel']);
        Route::resource('tipos-ocorrencia', TipoOcorrenciaController::class)->except(['show'])->parameters(['tipos-ocorrencia' => 'tipoOcorrencia']);
        Route::resource('justificativas', JustificativaController::class)->except(['show'])->parameters(['justificativas' => 'justificativa']);
    });
});
