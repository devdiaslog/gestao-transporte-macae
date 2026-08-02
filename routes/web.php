<?php

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
use App\Http\Controllers\ItemEntregaController;
use App\Http\Controllers\JustificativaController;
use App\Http\Controllers\MedicaoController;
use App\Http\Controllers\MetricasController;
use App\Http\Controllers\ModeloEquipamentoController;
use App\Http\Controllers\MotoristaController;
use App\Http\Controllers\OcorrenciaController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResponsavelController;
use App\Http\Controllers\SenhaController;
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

    // Foco do sistema: gestão de demandas. Quem tem acesso ao dashboard entra
    // pela visão gerencial; os demais, pela listagem. Sem nenhum dos dois,
    // resta o Mapa Geral (caso do perfil somente-consulta).
    return redirect()->route(match (true) {
        $user->can('dashboard.ver') => 'dashboard.demandas',
        $user->can('demandas.ver') => 'demandas.index',
        default => 'mapa-geral.index',
    });
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

// Senha do próprio usuário — disponível a qualquer autenticado
Route::middleware('auth')->group(function () {
    Route::get('minha-conta/senha', [SenhaController::class, 'editar'])->name('senha.editar');
    Route::put('minha-conta/senha', [SenhaController::class, 'atualizar'])->name('senha.atualizar');
});

// Mapa Geral — acessível a todos os perfis, inclusive Visualizador (acesso restrito apenas a isto)
Route::middleware('auth')->group(function () {
    Route::get('mapa-geral', [ControlTowerController::class, 'mapaGeralPagina'])->name('mapa-geral.index');
    Route::get('torre-de-controle/mapa-geral', [ControlTowerController::class, 'mapaGeral'])->name('control-tower.mapa-geral');
    Route::post('torre-de-controle/sincronizar-posicoes', [ControlTowerController::class, 'sincronizarPosicoes'])->name('control-tower.sincronizar-posicoes');
    Route::post('torre-de-controle/sincronizar-status-operacional', [ControlTowerController::class, 'sincronizarStatusOperacional'])->name('control-tower.sincronizar-status-operacional');
});

// Protected routes — todo o restante do sistema, indisponivel ao perfil Visualizador
Route::middleware(['auth', 'can:access-app'])->group(function () {
    /**
     * Aplica as permissões CRUD a um resource: cada ação exige `modulo.acao`
     * (listar só precisa de `.ver`, criar de `.criar`, e assim por diante).
     */
    $crud = fn ($resource, string $modulo) => $resource
        ->middlewareFor('index', "can:{$modulo}.ver")
        ->middlewareFor(['create', 'store'], "can:{$modulo}.criar")
        ->middlewareFor(['edit', 'update'], "can:{$modulo}.editar")
        ->middlewareFor('destroy', "can:{$modulo}.excluir");

    // Dashboard de Transporte — acesso por permissão individual
    Route::middleware('can:dashboard.ver')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'status'])->name('dashboard.status');
        Route::get('dashboard/graficos', [DashboardController::class, 'graficos'])->name('dashboard.graficos');
        Route::get('dashboard/cards', [DashboardController::class, 'tabela'])->name('dashboard.tabela');
        Route::get('dashboard/indicadores', [DashboardController::class, 'indicadores'])->name('dashboard.indicadores');
        Route::get('dashboard/demandas', [DashboardController::class, 'demandas'])->name('dashboard.demandas');
        Route::post('dashboard/atualizar', [DashboardController::class, 'atualizarManual'])->name('dashboard.atualizar')->middleware('can:dashboard.atualizar');
    });

    // Demandas de transporte
    Route::get('demandas', [DemandaController::class, 'index'])->name('demandas.index')->middleware('can:demandas.ver');
    Route::get('demandas-export', [DemandaController::class, 'export'])->name('demandas.export')->middleware('can:demandas.ver');
    Route::get('demandas-relatorio', [DemandaController::class, 'relatorio'])->name('demandas.relatorio')->middleware('can:demandas.ver');
    Route::get('demandas-modelo', [DemandaController::class, 'modeloImportacao'])->name('demandas.modelo')->middleware('can:demandas.importar');
    Route::post('demandas-importar', [DemandaController::class, 'importar'])->name('demandas.importar')->middleware('can:demandas.importar');
    Route::get('demandas/{demanda}/editar', [DemandaController::class, 'edit'])->name('demandas.edit')->middleware('can:demandas.editar');
    Route::put('demandas/{demanda}/status-etapa', [DemandaItemController::class, 'atualizarStatusEtapa'])->name('demandas.status-etapa')->middleware('can:demandas.editar');
    Route::put('demandas/{demanda}/entrega-etapa', [DemandaItemController::class, 'atualizarEntregaEtapa'])->name('demandas.entrega-etapa')->middleware('can:demandas.editar');
    Route::put('demandas/{demanda}/prazo-etapa', [DemandaItemController::class, 'atualizarPrazoEtapa'])->name('demandas.prazo-etapa')->middleware('can:demandas.editar');
    Route::post('demandas/{demanda}/itens', [DemandaItemController::class, 'store'])->name('demandas.itens.store')->middleware('can:demandas.editar');
    Route::post('demandas/{demanda}/itens-importar', [DemandaItemController::class, 'importar'])->name('demandas.itens.importar')->middleware('can:demandas.importar');
    Route::put('demanda-itens/{item}', [DemandaItemController::class, 'update'])->name('demanda-itens.update')->middleware('can:demandas.editar')->whereNumber('item');
    Route::delete('demanda-itens/{item}', [DemandaItemController::class, 'destroy'])->name('demanda-itens.destroy')->middleware('can:demandas.excluir')->whereNumber('item');
    // Itens de entrega: a visão do cliente, do item liberado no SAP até ser atendido.
    Route::get('itens-entrega', [ItemEntregaController::class, 'index'])->name('itens-entrega.index')->middleware('can:itens-entrega.ver');
    Route::get('itens-entrega-export', [ItemEntregaController::class, 'export'])->name('itens-entrega.export')->middleware('can:itens-entrega.ver');
    Route::post('itens-entrega/previsao', [ItemEntregaController::class, 'definirPrevisao'])->name('itens-entrega.previsao')->middleware('can:itens-entrega.prever');
    Route::post('itens-entrega/escopo', [ItemEntregaController::class, 'marcarForaEscopo'])->name('itens-entrega.escopo')->middleware('can:itens-entrega.escopo');
    Route::get('itens-entrega-modelo', [ItemEntregaController::class, 'modeloImportacao'])->name('itens-entrega.modelo')->middleware('can:itens-entrega.importar');
    Route::post('itens-entrega-importar', [ItemEntregaController::class, 'importar'])->name('itens-entrega.importar')->middleware('can:itens-entrega.importar');

    Route::post('demandas', [DemandaController::class, 'store'])->name('demandas.store')->middleware('can:demandas.criar');
    Route::put('demandas/{demanda}', [DemandaController::class, 'update'])->name('demandas.update')->middleware('can:demandas.editar');
    Route::patch('demandas/{demanda}/auditar', [DemandaController::class, 'auditar'])->name('demandas.auditar')->middleware('can:demandas.auditar');
    Route::delete('demandas/{demanda}', [DemandaController::class, 'destroy'])->name('demandas.destroy')->middleware('can:demandas.excluir');

    // Alertas da frota
    Route::get('alertas', [AlertaController::class, 'index'])->name('alertas.index')->middleware('can:alertas.ver');
    Route::get('alertas/pendentes', [AlertaController::class, 'pendentes'])->name('alertas.pendentes');
    Route::post('alertas', [AlertaController::class, 'store'])->name('alertas.store')->middleware('can:alertas.criar');
    Route::put('alertas/{alerta}', [AlertaController::class, 'update'])->name('alertas.update')->middleware('can:alertas.editar');
    Route::post('alertas/{alerta}/prorrogar', [AlertaController::class, 'prorrogar'])->name('alertas.prorrogar')->middleware('can:alertas.editar');
    Route::post('alertas/{alerta}/resolver', [AlertaController::class, 'resolver'])->name('alertas.resolver')->middleware('can:alertas.editar');
    Route::delete('alertas/{alerta}', [AlertaController::class, 'destroy'])->name('alertas.destroy')->middleware('can:alertas.excluir');

    // Ocorrências — acessível a todos os perfis (restrições de edição/exclusão tratadas no controller)
    Route::get('ocorrencias-export', [OcorrenciaController::class, 'export'])->name('ocorrencias.export')->middleware('can:ocorrencias.ver');
    Route::get('ocorrencias/veiculo/{equipamento}', [OcorrenciaController::class, 'veiculo'])->name('ocorrencias.veiculo')->middleware('can:ocorrencias.ver');
    Route::patch('ocorrencias/{ocorrencia}/auditoria', [OcorrenciaController::class, 'auditar'])->name('ocorrencias.auditar')->middleware('can:ocorrencias.auditar');
    $crud(Route::resource('ocorrencias', OcorrenciaController::class)->except(['show'])->parameters(['ocorrencias' => 'ocorrencia']), 'ocorrencias');

    // Bigcore (Elog)
    Route::get('bigcore/veiculo', [BigcoreController::class, 'veiculo'])->name('bigcore.veiculo');

    // Métricas
    Route::get('metricas/{periodo?}', [MetricasController::class, 'index'])
        ->where('periodo', 'hoje|7|30|90')
        ->name('metricas.index')->middleware('can:metricas.ver');

    // Reportes
    Route::get('reportes/ultimo-por-prefixo', [ReporteController::class, 'ultimoPorPrefixo'])->name('reportes.ultimo-por-prefixo')->middleware('can:reportes.ver');
    Route::get('reportes/{reporte}/data', [ReporteController::class, 'data'])->name('reportes.data')->middleware('can:reportes.ver');
    Route::resource('reportes', ReporteController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->parameters(['reportes' => 'reporte']);

    // Usuários
    Route::middleware('can:usuarios.ver')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('users-export', [UserController::class, 'export'])->name('users.export');
    });
    Route::post('users/{user}/resetar-senha', [UserController::class, 'resetarSenha'])
        ->name('users.resetar-senha')->middleware('can:usuarios.resetar-senha');

    // Perfis de acesso (papéis + permissões)
    $crud(Route::resource('perfis', PerfilController::class)->except(['show'])->parameters(['perfis' => 'perfil']), 'perfis');

    // Cadastros — cada módulo com sua permissão
    $crud(Route::resource('cercas', CercaController::class)->except(['show'])->parameters(['cercas' => 'cerca']), 'cercas');
    $crud(Route::resource('divisoes', DivisaoController::class)->except(['show'])->parameters(['divisoes' => 'divisao']), 'divisoes');
    Route::get('divisoes-export', [DivisaoController::class, 'export'])->name('divisoes.export')->middleware('can:divisoes.ver');
    $crud(Route::resource('subdivisoes', SubDivisaoController::class)->except(['show'])->parameters(['subdivisoes' => 'subDivisao']), 'subdivisoes');
    Route::get('subdivisoes-export', [SubDivisaoController::class, 'export'])->name('subdivisoes.export')->middleware('can:subdivisoes.ver');
    $crud(Route::resource('tipos-equipamentos', TipoEquipamentoController::class)->except(['show'])->parameters(['tipos-equipamentos' => 'tipoEquipamento']), 'tipos-equipamentos');
    Route::get('tipos-equipamentos-export', [TipoEquipamentoController::class, 'export'])->name('tipos-equipamentos.export')->middleware('can:tipos-equipamentos.ver');
    $crud(Route::resource('modelos-equipamentos', ModeloEquipamentoController::class)->except(['show'])->parameters(['modelos-equipamentos' => 'modeloEquipamento']), 'modelos-equipamentos');
    Route::get('modelos-equipamentos-export', [ModeloEquipamentoController::class, 'export'])->name('modelos-equipamentos.export')->middleware('can:modelos-equipamentos.ver');
    $crud(Route::resource('equipamentos', EquipamentoController::class)->except(['show'])->parameters(['equipamentos' => 'equipamento']), 'equipamentos');
    Route::get('equipamentos-export', [EquipamentoController::class, 'export'])->name('equipamentos.export')->middleware('can:equipamentos.ver');
    Route::patch('equipamentos/{equipamento}/operacional', [EquipamentoController::class, 'updateOperacional'])->name('equipamentos.operacional')->middleware('can:equipamentos.editar');
    $crud(Route::resource('motoristas', MotoristaController::class)->except(['show']), 'motoristas');
    $crud(Route::resource('medicoes', MedicaoController::class)->except(['show'])->parameters(['medicoes' => 'medicao']), 'medicoes');

    // Tabelas de apoio de ocorrências
    $crud(Route::resource('responsaveis', ResponsavelController::class)->except(['show'])->parameters(['responsaveis' => 'responsavel']), 'responsaveis');
    $crud(Route::resource('tipos-ocorrencia', TipoOcorrenciaController::class)->except(['show'])->parameters(['tipos-ocorrencia' => 'tipoOcorrencia']), 'tipos-ocorrencia');
    $crud(Route::resource('justificativas', JustificativaController::class)->except(['show'])->parameters(['justificativas' => 'justificativa']), 'justificativas');
});
