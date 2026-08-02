<?php

namespace App\Http\Controllers;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusSap;
use App\Http\Requests\DefinirPrevisaoRequest;
use App\Http\Requests\ImportarItensLiberadosRequest;
use App\Http\Requests\MarcarForaEscopoRequest;
use App\Models\DemandaItem;
use App\Services\ImportadorItensLiberados;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Visão do item de entrega — a ótica do cliente.
 *
 * Enquanto a tela de demandas mostra o atendimento, aqui o que importa é o
 * item: o cliente quer saber quando o material dele chega. O item aparece
 * desde a liberação no SAP (status 03), antes de existir veículo programado, e
 * sai quando é atendido, cancelado ou suspenso.
 */
class ItemEntregaController extends Controller
{
    /** Horizonte padrão do filtro: itens que vencem nos próximos 3 dias. */
    private const DIAS_PADRAO = 3;

    /** Abas da tela, cada uma com o recorte de status que representa. */
    private const ABAS = [
        'cobranca' => 'Em cobrança',
        'suspenso_externo' => 'Suspensos — cliente',
        'suspenso_interno' => 'Suspensos — interno',
        'encerrados' => 'Encerrados',
    ];

    public function index(Request $request): View
    {
        $aba = array_key_exists((string) $request->input('aba'), self::ABAS)
            ? (string) $request->input('aba')
            : 'cobranca';

        $dias = $request->has('dias') && $request->input('dias') !== ''
            ? max(0, (int) $request->input('dias'))
            : self::DIAS_PADRAO;

        $itens = $this->queryFiltrada($request, $aba, $dias)
            ->with(['demanda.equipamento', 'previsaoAtual.autor', 'marcadoForaDoEscopoPor'])
            ->orderByRaw('prazo_item is null')
            ->orderBy('prazo_item')
            ->paginate(50)
            ->withQueryString();

        return view('itens-entrega.index', [
            'itens' => $itens,
            'aba' => $aba,
            'abas' => self::ABAS,
            'dias' => $dias,
            'resumo' => $this->resumo($request, $aba, $dias),
            'contadoresAba' => $this->contadoresPorAba($request, $dias),
            'origens' => $this->valoresDistintos('local_origem'),
            'destinos' => $this->valoresDistintos('local_destino'),
            'filtros' => $request->only(['busca', 'origem', 'destino', 'situacao', 'doc_unitizacao', 'ausentes']),
        ]);
    }

    /**
     * Registra a previsão nos itens selecionados.
     *
     * O lançamento é em lote por natureza: a operação define a data olhando
     * para um grupo (mesma origem→destino, ou o mesmo contentor), não item a
     * item. Cada item recebe sua própria linha no histórico.
     */
    public function definirPrevisao(DefinirPrevisaoRequest $request): RedirectResponse
    {
        $ids = $request->validated('itens');
        $previsao = now()->parse($request->validated('data_hora_previsao'));
        $origem = count($ids) > 1 ? OrigemPrevisao::Lote : OrigemPrevisao::Manual;

        $alterados = 0;

        DB::transaction(function () use ($ids, $previsao, $origem, $request, &$alterados) {
            foreach (DemandaItem::whereIn('id', $ids)->get() as $item) {
                $registro = $item->registrarPrevisao(
                    $previsao,
                    $origem,
                    $request->user()->id,
                    $request->validated('motivo'),
                );

                if ($registro !== null) {
                    $alterados++;
                }
            }
        });

        $ignorados = count($ids) - $alterados;

        return back()->with('success', trim(sprintf(
            'Previsão de %s registrada em %d %s.%s',
            $previsao->format('d/m/Y H:i'),
            $alterados,
            $alterados === 1 ? 'item' : 'itens',
            $ignorados > 0 ? " {$ignorados} já estava(m) com essa previsão." : '',
        )));
    }

    /**
     * Marca ou desmarca itens como fora da nossa responsabilidade.
     */
    public function marcarForaEscopo(MarcarForaEscopoRequest $request): RedirectResponse
    {
        $ids = $request->validated('itens');
        $fora = $request->boolean('fora_escopo');

        DB::transaction(function () use ($ids, $fora, $request) {
            foreach (DemandaItem::whereIn('id', $ids)->get() as $item) {
                $fora
                    ? $item->marcarForaDoEscopo($request->validated('justificativa'), $request->user()->id)
                    : $item->reverterForaDoEscopo();
            }
        });

        $total = count($ids);

        return back()->with('success', sprintf(
            '%d %s %s como fora do nosso escopo.',
            $total,
            $total === 1 ? 'item' : 'itens',
            $fora ? 'marcado(s)' : 'devolvido(s) ao escopo',
        ));
    }

    /**
     * Importa a planilha de itens em cobrança exportada do SAP.
     */
    public function importar(ImportarItensLiberadosRequest $request, ImportadorItensLiberados $importador): RedirectResponse
    {
        $resultado = $importador->importar(
            $request->file('arquivo')->getRealPath(),
            $request->user()->id,
            $request->boolean('marcar_ausentes'),
        );

        if ($resultado['erros'] !== []) {
            return back()->with('error', 'Importação não concluída: '.implode(' · ', array_slice($resultado['erros'], 0, 3)));
        }

        $msg = sprintf(
            '%d item(ns) importado(s), %d atualizado(s).',
            $resultado['itens_criados'],
            $resultado['itens_atualizados'],
        );

        if ($resultado['itens_ausentes'] > 0) {
            $msg .= sprintf(' %d não constava(m) na planilha e foi(ram) marcado(s) para conferência.', $resultado['itens_ausentes']);
        }

        if ($resultado['avisos'] !== []) {
            $msg .= ' '.implode(' · ', array_slice($resultado['avisos'], 0, 3));
        }

        return back()->with('success', $msg);
    }

    /**
     * Planilha modelo com o cabeçalho aceito e duas linhas de exemplo.
     */
    public function modeloImportacao(ImportadorItensLiberados $importador): BinaryFileResponse
    {
        return response()
            ->download($importador->gerarModelo(), 'modelo-importacao-itens-entrega.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Exportação para o cliente: os mesmos itens que a tela mostra, com a
     * previsão e a situação diante do prazo.
     */
    public function export(Request $request): Response
    {
        $aba = array_key_exists((string) $request->input('aba'), self::ABAS)
            ? (string) $request->input('aba')
            : 'cobranca';

        $dias = $request->has('dias') && $request->input('dias') !== ''
            ? max(0, (int) $request->input('dias'))
            : self::DIAS_PADRAO;

        $itens = $this->queryFiltrada($request, $aba, $dias)
            ->with(['demanda.equipamento', 'previsaoAtual.autor'])
            ->orderByRaw('prazo_item is null')
            ->orderBy('prazo_item')
            ->get();

        $fmt = fn ($dt) => $dt?->format('d/m/Y H:i') ?? '';

        $headers = [
            'RT', 'Item', 'Subitem', 'Descrição da Carga', 'Origem', 'Retirada', 'Destino',
            'Peso (kg)', 'Contentor', 'Grupo Planejamento',
            'Criada em', 'Liberada em', 'Prazo', 'Previsão', 'Situação',
            'Status SAP', 'Atendimento', 'Veículo', 'Fora do Escopo', 'Justificativa',
        ];

        $rows = $itens->map(fn (DemandaItem $i) => [
            $i->numero_rt,
            $i->numero_item,
            $i->subitem ?? '',
            $i->descricao_item ?? '',
            $i->local_origem ?? '',
            $i->descricao_local_retirada ?? '',
            $i->local_destino ?? '',
            $i->peso_total ?? '',
            $i->doc_unitizacao_superior ?? '',
            $i->grupo_planejamento ?? '',
            $fmt($i->data_hora_criacao_rt),
            $fmt($i->data_hora_liberacao_rt),
            $fmt($i->prazo_item),
            $fmt($i->data_hora_previsao_entrega),
            self::rotuloSituacao($i->situacaoPrevisao()),
            $i->status_sap?->label() ?? '',
            $i->demanda?->numero_demanda ?? '',
            $i->demanda?->equipamento?->prefixo ?? '',
            $i->fora_escopo ? 'Sim' : 'Não',
            $i->fora_escopo_justificativa ?? '',
        ]);

        $csv = collect([$headers])
            ->concat($rows)
            ->map(fn (array $row) => implode(';', array_map(fn ($cell) => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n");

        $filename = 'itens_entrega_'.now()->format('Y-m-d_H-i').'.csv';

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @return Builder<DemandaItem>
     */
    private function queryFiltrada(Request $request, string $aba, int $dias): Builder
    {
        return DemandaItem::query()
            ->tap(fn (Builder $q) => $this->aplicarAba($q, $aba))
            // Horizonte: o cliente pede a visão antecipada (D+3 por padrão).
            // Itens já vencidos entram sempre — são os mais urgentes.
            ->when($dias > 0 && $aba === 'cobranca', fn (Builder $q) => $q
                ->where(fn (Builder $s) => $s
                    ->whereNull('prazo_item')
                    ->orWhere('prazo_item', '<=', now()->addDays($dias)->endOfDay())))
            ->when($request->filled('busca'), function (Builder $q) use ($request) {
                $busca = trim((string) $request->input('busca'));
                $q->where(fn (Builder $s) => $s
                    ->where('numero_rt', 'like', "%{$busca}%")
                    ->orWhere('descricao_item', 'like', "%{$busca}%")
                    ->orWhere('doc_unitizacao_superior', 'like', "%{$busca}%"));
            })
            ->when($request->filled('origem'), fn (Builder $q) => $q->where('local_origem', $request->input('origem')))
            ->when($request->filled('destino'), fn (Builder $q) => $q->where('local_destino', $request->input('destino')))
            ->when($request->filled('doc_unitizacao'), fn (Builder $q) => $q->where('doc_unitizacao_superior', $request->input('doc_unitizacao')))
            ->when($request->boolean('ausentes'), fn (Builder $q) => $q->whereNotNull('ausente_no_sap_em'))
            ->when($request->filled('situacao'), fn (Builder $q) => $this->aplicarSituacao($q, (string) $request->input('situacao')));
    }

    /**
     * @param  Builder<DemandaItem>  $query
     */
    private function aplicarAba(Builder $query, string $aba): void
    {
        match ($aba) {
            'suspenso_externo' => $query->where('status_sap', StatusSap::SuspensoExterno),
            'suspenso_interno' => $query->where('status_sap', StatusSap::SuspensoInterno),
            'encerrados' => $query->whereIn('status_sap', [StatusSap::Atendido, StatusSap::Cancelado]),
            default => $query->whereIn('status_sap', [StatusSap::Liberado, StatusSap::Programado]),
        };
    }

    /**
     * O semáforo que o cliente enxerga, traduzido para SQL.
     *
     * @param  Builder<DemandaItem>  $query
     */
    private function aplicarSituacao(Builder $query, string $situacao): void
    {
        match ($situacao) {
            'fora_escopo' => $query->where('fora_escopo', true),
            'sem_previsao' => $query->where('fora_escopo', false)->whereNull('data_hora_previsao_entrega'),
            'no_prazo' => $query->where('fora_escopo', false)
                ->whereNotNull('data_hora_previsao_entrega')
                ->whereNotNull('prazo_item')
                ->whereColumn('data_hora_previsao_entrega', '<=', 'prazo_item'),
            'fora_do_prazo' => $query->where('fora_escopo', false)
                ->whereNotNull('data_hora_previsao_entrega')
                ->whereNotNull('prazo_item')
                ->whereColumn('data_hora_previsao_entrega', '>', 'prazo_item'),
            default => null,
        };
    }

    /**
     * Contadores do semáforo, respeitando os demais filtros da tela.
     *
     * @return array<string, int>
     */
    private function resumo(Request $request, string $aba, int $dias): array
    {
        $resumo = [];

        foreach (['no_prazo', 'fora_do_prazo', 'sem_previsao', 'fora_escopo'] as $situacao) {
            $resumo[$situacao] = $this->queryFiltrada($request, $aba, $dias)
                ->tap(fn (Builder $q) => $this->aplicarSituacao($q, $situacao))
                ->count();
        }

        $resumo['total'] = $this->queryFiltrada($request, $aba, $dias)->count();

        return $resumo;
    }

    /**
     * Quantos itens há em cada aba — é o número que o gerente leva ao cliente
     * ("estes N estão parados esperando você").
     *
     * @return array<string, int>
     */
    private function contadoresPorAba(Request $request, int $dias): array
    {
        $contadores = [];

        foreach (array_keys(self::ABAS) as $aba) {
            $contadores[$aba] = $this->queryFiltrada($request, $aba, $dias)->count();
        }

        return $contadores;
    }

    /**
     * @return array<int, string>
     */
    private function valoresDistintos(string $coluna): array
    {
        return DemandaItem::query()
            ->whereNotNull($coluna)
            ->distinct()
            ->orderBy($coluna)
            ->pluck($coluna)
            ->all();
    }

    public static function rotuloSituacao(string $situacao): string
    {
        return match ($situacao) {
            'no_prazo' => 'No prazo',
            'fora_do_prazo' => 'Fora do prazo',
            'sem_previsao' => 'Sem previsão',
            'sem_prazo' => 'Sem prazo',
            default => $situacao,
        };
    }
}
