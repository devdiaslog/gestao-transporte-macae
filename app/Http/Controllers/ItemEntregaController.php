<?php

namespace App\Http\Controllers;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusSap;
use App\Http\Requests\AjustarRotaRequest;
use App\Http\Requests\DefinirPrevisaoRequest;
use App\Http\Requests\ImportarItensLiberadosRequest;
use App\Http\Requests\MarcarForaEscopoRequest;
use App\Http\Requests\RenegociarPrazoRequest;
use App\Models\DemandaItem;
use App\Services\ImportadorItensLiberados;
use App\Support\ContentorSap;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
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

    /**
     * Status que a tela permite filtrar, na ordem do ciclo de vida.
     *
     * Atendido fica de fora: item entregue deixou de ser cobrado e não tem o
     * que replanejar.
     */
    private const STATUS_FILTRAVEIS = [
        StatusSap::Liberado,
        StatusSap::Programado,
        StatusSap::SuspensoInterno,
        StatusSap::SuspensoExterno,
        StatusSap::Cancelado,
    ];

    /** Status considerados quando o usuário não escolhe nenhum. */
    private const STATUS_PADRAO = [StatusSap::Liberado, StatusSap::Programado];

    /**
     * Recortes de previsão — o que o operador busca quando vai replanejar.
     */
    private const FILTROS_PREVISAO = [
        'sem_previsao' => 'Sem previsão',
        'vencida' => 'Previsão vencida',
        'proxima' => 'Previsão vence em até',
    ];

    /**
     * Semáforo da previsão: rótulo e cores de cada situação.
     *
     * Vive aqui, e não na view, porque as duas telas e o export precisam do
     * mesmo vocabulário — o cliente lê "Fora do prazo" no CSV e na tela.
     */
    private const CORES_SITUACAO = [
        'no_prazo' => ['label' => 'Previsto no prazo', 'dot' => 'bg-emerald-500', 'chip' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'],
        'fora_do_prazo' => ['label' => 'Previsto fora do prazo', 'dot' => 'bg-red-500', 'chip' => 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400'],
        'sem_previsao' => ['label' => 'Sem previsão', 'dot' => 'bg-zinc-400', 'chip' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'],
        'sem_prazo' => ['label' => 'Sem prazo', 'dot' => 'bg-sky-400', 'chip' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400'],
        'fora_escopo' => ['label' => 'Fora do escopo', 'dot' => 'bg-zinc-700', 'chip' => 'bg-zinc-800 text-zinc-100 dark:bg-zinc-700 dark:text-zinc-200'],
    ];

    /** Situações que viram card no topo — fora do escopo não é gerido aqui. */
    private const SITUACOES_RESUMO = ['no_prazo', 'fora_do_prazo', 'sem_previsao'];

    /**
     * Lista os trechos (origem→destino) com os totais de cada um.
     *
     * A operação raciocina por fluxo, não por item solto: é olhando o trecho
     * que se decide a previsão. O detalhe de cada item fica na página seguinte.
     */
    public function index(Request $request): View
    {
        $status = $this->statusDe($request);
        $dias = $this->diasDe($request);

        // Área de piso: item sem embalagem vale pelas próprias medidas; dentro
        // de uma embalagem superior vale a área dela, somada à parte para
        // contar cada uma uma vez só.
        $trechos = $this->queryFiltrada($request, $status, $dias)
            ->selectRaw('
                local_origem_norm,
                local_destino_norm,
                count(*) as total,
                sum(coalesce(peso_total, 0)) as peso,
                sum(case when doc_unitizacao_superior is null and numero_contentor is null
                              and coalesce(comprimento, 0) * coalesce(largura, 0) <= 100
                         then coalesce(comprimento, 0) * coalesce(largura, 0)
                         else 0 end) as area_solta,
                sum(case when coalesce(comprimento, 0) * coalesce(largura, 0) > 100
                         then 1 else 0 end) as medidas_suspeitas,
                sum(case when fora_escopo = 0 and data_hora_previsao_entrega is null then 1 else 0 end) as sem_previsao,
                sum(case when fora_escopo = 0 and data_hora_previsao_entrega is not null
                          and prazo_item is not null
                          and data_hora_previsao_entrega <= prazo_item then 1 else 0 end) as no_prazo,
                sum(case when fora_escopo = 0 and data_hora_previsao_entrega is not null
                          and prazo_item is not null
                          and data_hora_previsao_entrega > prazo_item then 1 else 0 end) as fora_do_prazo,
                sum(case when fora_escopo = 1 then 1 else 0 end) as fora_escopo,
                min(prazo_item) as prazo_mais_proximo,
                count(distinct doc_unitizacao_superior) as embalagens
            ')
            ->groupBy('local_origem_norm', 'local_destino_norm')
            ->orderByDesc('sem_previsao')
            ->orderByDesc('total')
            ->get();

        $areaPorTrecho = $this->areaDasEmbalagensPorTrecho($request, $status, $dias);

        $trechos->each(function ($trecho) use ($areaPorTrecho) {
            $chave = $trecho->local_origem_norm.'|'.$trecho->local_destino_norm;
            $trecho->area = round((float) $trecho->area_solta + ($areaPorTrecho[$chave] ?? 0), 2);
        });

        return view('itens-entrega.index', [
            'trechos' => $trechos,
            'statusSelecionados' => array_map(fn (StatusSap $s) => $s->value, $status),
            'statusDisponiveis' => self::STATUS_FILTRAVEIS,
            'filtrosPrevisao' => self::FILTROS_PREVISAO,
            'situacoesResumo' => self::SITUACOES_RESUMO,
            'dias' => $dias,
            'diasPrevisao' => $this->diasPrevisaoDe($request),
            'resumo' => $this->resumo($request, $status, $dias),
            'cores' => self::CORES_SITUACAO,
            'filtros' => $request->only(['busca', 'situacao', 'ausentes', 'previsao']),
        ]);
    }

    /**
     * Itens de um trecho — onde a operação efetivamente trabalha.
     */
    public function trecho(Request $request): View
    {
        $status = $this->statusDe($request);
        $dias = $this->diasDe($request);

        $itens = $this->queryFiltrada($request, $status, $dias)
            ->with(['demanda.equipamento', 'previsaoAtual.autor', 'marcadoForaDoEscopoPor'])
            // Itens da mesma embalagem ficam juntos: eles viajam juntos.
            ->orderByRaw('coalesce(doc_unitizacao_superior, numero_contentor) is null')
            ->orderByRaw('coalesce(doc_unitizacao_superior, numero_contentor)')
            ->orderByRaw('prazo_item is null')
            ->orderBy('prazo_item')
            ->paginate(50)
            ->withQueryString();

        return view('itens-entrega.trecho', [
            'itens' => $itens,
            'embalagens' => $this->embalagensDaPagina($itens->getCollection()),
            'areaDePiso' => ContentorSap::areaDePiso($itens->getCollection()),
            'statusSelecionados' => array_map(fn (StatusSap $s) => $s->value, $status),
            'statusDisponiveis' => self::STATUS_FILTRAVEIS,
            'filtrosPrevisao' => self::FILTROS_PREVISAO,
            'situacoesResumo' => self::SITUACOES_RESUMO,
            'dias' => $dias,
            'diasPrevisao' => $this->diasPrevisaoDe($request),
            'origemTrecho' => $request->input('origem_norm'),
            'destinoTrecho' => $request->input('destino_norm'),
            'resumo' => $this->resumo($request, $status, $dias),
            'locais' => $this->locaisConhecidos(),
            'cores' => self::CORES_SITUACAO,
            'filtros' => $request->only(['busca', 'situacao', 'doc_unitizacao', 'ausentes', 'origem_norm', 'destino_norm', 'previsao']),
        ]);
    }

    /**
     * Status escolhidos no filtro; sem escolha, os que o cliente cobra.
     *
     * @return array<int, StatusSap>
     */
    private function statusDe(Request $request): array
    {
        $escolhidos = collect((array) $request->input('status', []))
            ->map(fn ($codigo) => StatusSap::fromCodigo($codigo))
            ->filter()
            ->filter(fn (StatusSap $s) => in_array($s, self::STATUS_FILTRAVEIS, true))
            ->values()
            ->all();

        return $escolhidos !== [] ? $escolhidos : self::STATUS_PADRAO;
    }

    private function diasDe(Request $request): int
    {
        return $request->has('dias') && $request->input('dias') !== ''
            ? max(0, (int) $request->input('dias'))
            : self::DIAS_PADRAO;
    }

    /**
     * Horizonte do filtro "previsão vence em até N dias".
     */
    private function diasPrevisaoDe(Request $request): int
    {
        return $request->has('dias_previsao') && $request->input('dias_previsao') !== ''
            ? max(0, (int) $request->input('dias_previsao'))
            : 1;
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
        $um = $total === 1;

        return back()->with('success', sprintf(
            '%d %s %s.',
            $total,
            $um ? 'item' : 'itens',
            $fora
                ? ($um ? 'marcado como fora do nosso escopo' : 'marcados como fora do nosso escopo')
                : ($um ? 'devolvido ao nosso escopo' : 'devolvidos ao nosso escopo'),
        ));
    }

    /**
     * Registra um prazo renegociado com o cliente.
     *
     * Item programado cujo prazo foi acordado de novo não está atrasado — a
     * tela mostrava "fora do prazo" contra uma data que não vale mais. A partir
     * daqui vale o prazo acordado, e o do SAP fica visível ao lado para a
     * renegociação não desaparecer.
     */
    public function renegociarPrazo(RenegociarPrazoRequest $request): RedirectResponse
    {
        $ids = $request->validated('itens');
        $prazo = now()->parse($request->validated('prazo_item'));

        DB::transaction(function () use ($ids, $prazo, $request) {
            foreach (DemandaItem::whereIn('id', $ids)->get() as $item) {
                $item->renegociarPrazo($prazo, $request->user()->id, $request->validated('motivo'));
            }
        });

        $total = count($ids);

        return back()->with('success', sprintf(
            'Prazo de %d %s alterado para %s. O SAP não vai mais sobrescrever %s.',
            $total,
            $total === 1 ? 'item' : 'itens',
            $prazo->format('d/m/Y H:i'),
            $total === 1 ? 'este prazo' : 'estes prazos',
        ));
    }

    /**
     * Corrige a origem e o destino dos itens selecionados.
     *
     * A normalização automática só resolve grafia — não sabe dizer se
     * "ARM-MACAE (SUCATA)" é o mesmo pátio que "ARM-MACAE". Quem sabe é a
     * operação, e é aqui que ela corrige. Os campos passam a contar como
     * editados pelo operador, então a próxima importação não os desfaz.
     */
    public function ajustarRota(AjustarRotaRequest $request): RedirectResponse
    {
        $ids = $request->validated('itens');
        $origem = $request->validated('local_origem');
        $destino = $request->validated('local_destino');

        DB::transaction(function () use ($ids, $origem, $destino) {
            foreach (DemandaItem::whereIn('id', $ids)->get() as $item) {
                $editados = $item->campos_editados ?? [];

                if ($origem !== null) {
                    $item->local_origem = $origem;
                    $editados[] = 'local_origem';
                }

                if ($destino !== null) {
                    $item->local_destino = $destino;
                    $editados[] = 'local_destino';
                }

                $item->campos_editados = array_values(array_unique($editados));
                $item->save();
            }
        });

        $total = count($ids);
        $um = $total === 1;

        return back()->with('success', sprintf(
            'Rota ajustada em %d %s. O SAP não vai mais sobrescrever %s %s.',
            $total,
            $um ? 'item' : 'itens',
            match (true) {
                $origem !== null && $destino !== null => 'a origem e o destino',
                $origem !== null => 'a origem',
                default => 'o destino',
            },
            $um ? 'deste item' : 'destes itens',
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
        $itens = $this->queryFiltrada($request, $this->statusDe($request), $this->diasDe($request))
            ->with(['demanda.equipamento', 'previsaoAtual.autor'])
            ->orderByRaw('prazo_item is null')
            ->orderBy('prazo_item')
            ->get();

        $fmt = fn ($dt) => $dt?->format('d/m/Y H:i') ?? '';

        $headers = [
            'RT', 'Item', 'Subitem', 'Descrição da Carga', 'Origem', 'Retirada', 'Destino',
            'Peso (kg)', 'Comprimento (m)', 'Largura (m)', 'Altura (m)', 'Área (m²)',
            'Contentor', 'Grupo Planejamento',
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
            $i->comprimento ?? '',
            $i->largura ?? '',
            $i->altura ?? '',
            $i->area() ?? '',
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
    private function queryFiltrada(Request $request, array $status, int $dias): Builder
    {
        return DemandaItem::query()
            ->whereIn('status_sap', array_map(fn (StatusSap $s) => $s->value, $status))
            // Horizonte: o cliente pede a visão antecipada (D+3 por padrão).
            // Itens já vencidos entram sempre — são os mais urgentes.
            ->when($dias > 0, fn (Builder $q) => $q
                ->where(fn (Builder $s) => $s
                    ->whereNull('prazo_item')
                    ->orWhere('prazo_item', '<=', now()->addDays($dias)->endOfDay())))
            ->when($request->filled('busca'), function (Builder $q) use ($request) {
                $busca = trim((string) $request->input('busca'));
                $q->where(fn (Builder $s) => $s
                    ->where('numero_rt', 'like', "%{$busca}%")
                    ->orWhere('descricao_item', 'like', "%{$busca}%")
                    ->orWhere('doc_unitizacao_superior', 'like', "%{$busca}%")
                    ->orWhere('numero_contentor', 'like', "%{$busca}%")
                    // Número do atendimento: o operador costuma ter a viagem em
                    // mãos e querer saber o que ela carrega.
                    ->orWhereHas('demanda', fn (Builder $d) => $d->where('numero_demanda', 'like', "%{$busca}%")));
            })
            // O trecho é filtrado pela forma canônica: as variações de grafia do
            // SAP ("ARM-MACAE", "ARM MACAÉ") são o mesmo lugar.
            ->when($request->filled('origem_norm'), fn (Builder $q) => $q->where('local_origem_norm', $request->input('origem_norm')))
            ->when($request->filled('destino_norm'), fn (Builder $q) => $q->where('local_destino_norm', $request->input('destino_norm')))
            ->when($request->filled('doc_unitizacao'), fn (Builder $q) => $q->where('doc_unitizacao_superior', $request->input('doc_unitizacao')))
            ->when($request->filled('contentor'), fn (Builder $q) => $q->where(fn (Builder $s) => $s
                ->where('doc_unitizacao_superior', $request->input('contentor'))
                ->orWhere('numero_contentor', $request->input('contentor'))))
            ->when($request->boolean('ausentes'), fn (Builder $q) => $q->whereNotNull('ausente_no_sap_em'))
            ->when($request->filled('situacao'), fn (Builder $q) => $this->aplicarSituacao($q, (string) $request->input('situacao')))
            ->when($request->filled('previsao'), fn (Builder $q) => $this->aplicarFiltroPrevisao(
                $q,
                (string) $request->input('previsao'),
                $this->diasPrevisaoDe($request),
            ));
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
     * Recortes usados para replanejar a previsão.
     *
     * @param  Builder<DemandaItem>  $query
     */
    private function aplicarFiltroPrevisao(Builder $query, string $filtro, int $dias): void
    {
        match ($filtro) {
            'sem_previsao' => $query->whereNull('data_hora_previsao_entrega'),
            // Prometido e não cumprido: a data passou e o item continua aqui.
            'vencida' => $query->whereNotNull('data_hora_previsao_entrega')
                ->where('data_hora_previsao_entrega', '<', now()),
            // Vence logo: dá tempo de confirmar ou remarcar antes de falhar.
            'proxima' => $query->whereNotNull('data_hora_previsao_entrega')
                ->whereBetween('data_hora_previsao_entrega', [now(), now()->addDays($dias)->endOfDay()]),
            default => null,
        };
    }

    /**
     * Contadores do semáforo, respeitando os demais filtros da tela.
     *
     * @return array<string, int>
     */
    private function resumo(Request $request, array $status, int $dias): array
    {
        $resumo = [];

        foreach (self::SITUACOES_RESUMO as $situacao) {
            $resumo[$situacao] = $this->queryFiltrada($request, $status, $dias)
                ->tap(fn (Builder $q) => $this->aplicarSituacao($q, $situacao))
                ->count();
        }

        $resumo['total'] = $this->queryFiltrada($request, $status, $dias)->count();

        return $resumo;
    }

    /**
     * Área de piso das embalagens superiores, por trecho.
     *
     * Cada embalagem conta uma vez só — a query principal não consegue fazer
     * isso num único GROUP BY, porque somaria a área do contentor tantas vezes
     * quantos itens ele carrega.
     *
     * Uma embalagem tem sempre um único trecho: ela é montada para um destino.
     * É essa garantia da operação que permite somar por trecho sem risco de
     * contar a mesma embalagem em dois lugares.
     *
     * @return array<string, float> chave "origem|destino"
     */
    private function areaDasEmbalagensPorTrecho(Request $request, array $status, int $dias): array
    {
        return $this->queryFiltrada($request, $status, $dias)
            ->where(fn (Builder $q) => $q->whereNotNull('doc_unitizacao_superior')->orWhereNotNull('numero_contentor'))
            ->get(['local_origem_norm', 'local_destino_norm', 'doc_unitizacao_superior', 'numero_contentor', 'area_embalagem', 'comprimento', 'largura'])
            ->groupBy(fn (DemandaItem $i) => $i->local_origem_norm.'|'.$i->local_destino_norm)
            ->map(fn ($doTrecho) => ContentorSap::areaDePiso($doTrecho))
            ->all();
    }

    /**
     * Resumo das embalagens presentes na página.
     *
     * O que viaja é o contentor: saber quantos itens ele carrega, quanto pesa
     * no total e quanto ocupa de piso é o que permite decidir o veículo.
     *
     * @param  Collection<int, DemandaItem>  $itens
     * @return Collection<string, array{descricao: string|null, itens: int, peso: float, area: float|null, sem_previsao: int}>
     */
    private function embalagensDaPagina(Collection $itens): Collection
    {
        return $itens
            ->filter(fn (DemandaItem $i) => $i->embalagemSuperior() !== null)
            ->groupBy(fn (DemandaItem $i) => $i->embalagemSuperior())
            ->map(fn ($daEmbalagem) => [
                'descricao' => $daEmbalagem->first()->descricao_contentor,
                'itens' => $daEmbalagem->count(),
                'peso' => (float) $daEmbalagem->sum('peso_total'),
                // A área é da embalagem, não a soma do que está dentro dela.
                'area' => $daEmbalagem->first()->area_embalagem !== null
                    ? (float) $daEmbalagem->first()->area_embalagem
                    : null,
                'sem_previsao' => $daEmbalagem->whereNull('data_hora_previsao_entrega')->count(),
            ])
            ->sortByDesc('itens');
    }

    /**
     * Locais já usados, na forma canônica — as opções do ajuste de rota.
     *
     * @return array<int, string>
     */
    private function locaisConhecidos(): array
    {
        $origens = DemandaItem::query()->whereNotNull('local_origem_norm')->distinct()->pluck('local_origem_norm');
        $destinos = DemandaItem::query()->whereNotNull('local_destino_norm')->distinct()->pluck('local_destino_norm');

        return $origens->concat($destinos)->unique()->sort()->values()->all();
    }

    public static function rotuloSituacao(string $situacao): string
    {
        return self::CORES_SITUACAO[$situacao]['label'] ?? $situacao;
    }
}
