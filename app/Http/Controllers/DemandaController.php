<?php

namespace App\Http\Controllers;

use App\Enums\StatusDemanda;
use App\Enums\TipoCadastro;
use App\Http\Requests\ImportarDemandasRequest;
use App\Http\Requests\StoreDemandaRequest;
use App\Http\Requests\UpdateDemandaRequest;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\Equipamento;
use App\Services\DemandaCalculadora;
use App\Services\ImportadorDemandas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DemandaController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filtroKeys = ['q', 'status', 'tipo', 'fonte', 'prefixo', 'data_de', 'data_ate', 'prazo_de', 'prazo_ate', 'ajuste'];

        if ($request->boolean('reset')) {
            session()->forget('demandas.filtros');

            return redirect()->route('demandas.index');
        }

        $temFiltro = $request->hasAny($filtroKeys) || $request->has('page');

        if (! $temFiltro) {
            // Considera apenas filtros com valor: um conjunto só de vazios/null
            // seria descartado pelo route() e cairia em loop de redirect.
            $salvos = array_filter(
                session('demandas.filtros', []),
                fn ($v) => $v !== null && $v !== ''
            );
            if (! empty($salvos)) {
                return redirect()->route('demandas.index', $salvos);
            }
        } elseif ($request->hasAny($filtroKeys)) {
            session(['demandas.filtros' => $request->only($filtroKeys)]);
        }

        $search = $request->input('q');
        // Padrão da tela: atendimentos em andamento.
        $status = $request->input('status', StatusDemanda::EmAndamento->value);
        $tipo = $request->input('tipo');
        $fonte = $request->input('fonte');
        $prefixo = $request->input('prefixo');
        $dataDE = $request->input('data_de');
        $dataAte = $request->input('data_ate');
        $prazoDE = $request->input('prazo_de');
        $prazoAte = $request->input('prazo_ate');
        $ajuste = $request->input('ajuste');

        $base = $this->queryFiltrada($request, $status);

        $totalItensFiltro = DemandaItem::query()
            ->whereIn('demanda_id', (clone $base)->select('id'))
            ->count();

        $demandas = $base
            ->with(['equipamento', 'criador', 'itens'])
            ->when($prazoDE || $prazoAte, fn ($q) => $q->orderBy('prazo_demanda'), fn ($q) => $q->latest())
            ->paginate(10)
            ->appends([
                'q' => $search ?? '',
                'status' => $status ?? '',
                'tipo' => $tipo ?? '',
                'fonte' => $fonte ?? '',
                'prefixo' => $prefixo ?? '',
                'data_de' => $dataDE ?? '',
                'data_ate' => $dataAte ?? '',
                'prazo_de' => $prazoDE ?? '',
                'prazo_ate' => $prazoAte ?? '',
                'ajuste' => $ajuste ?? '',
            ]);

        $equipamentos = Equipamento::query()
            ->whereHas('tipo', fn ($q) => $q->where('nome', 'Motorizado'))
            ->whereNotNull('prefixo')
            ->orderBy('prefixo')
            ->get(['id', 'prefixo', 'placa']);

        return view('demandas.index', compact('demandas', 'equipamentos', 'totalItensFiltro', 'search', 'status', 'tipo', 'fonte', 'prefixo', 'dataDE', 'dataAte', 'prazoDE', 'prazoAte', 'ajuste'));
    }

    /**
     * Monta a query de demandas com todos os filtros da tela — usada pela
     * listagem, pelo contador de itens e pelo export (mesmo resultado).
     */
    private function queryFiltrada(Request $request, ?string $status): Builder
    {
        return Demanda::query()
            ->when($request->input('q'), fn ($q, $v) => $q->where(fn ($sub) => $sub->where('numero_demanda', 'like', "%{$v}%")
                ->orWhere('documento_demanda', 'like', "%{$v}%")))
            ->when($status === 'active', fn ($q) => $q->whereIn('status_demanda', ['pendente', 'em_andamento']))
            ->when($status && $status !== 'active', fn ($q) => $q->where('status_demanda', $status))
            ->when($request->input('tipo'), fn ($q, $v) => $q->where('tipo_demanda', $v))
            ->when($request->input('fonte'), fn ($q, $v) => $q->where('fonte_demanda', $v))
            ->when($request->input('prefixo'), fn ($q, $v) => $q->whereHas('equipamento', fn ($eq) => $eq->where('prefixo', 'like', "%{$v}%")))
            ->when($request->input('data_de'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->input('data_ate'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->input('prazo_de'), fn ($q, $v) => $q->whereNotNull('prazo_demanda')->where('prazo_demanda', '>=', Carbon::parse($v)))
            ->when($request->input('prazo_ate'), fn ($q, $v) => $q->whereNotNull('prazo_demanda')->where('prazo_demanda', '<=', Carbon::parse($v)))
            ->when($request->input('ajuste') === 'pendente', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('inicio_automatico', true)->orWhere('fim_automatico', true)));
    }

    public function edit(Demanda $demanda): View
    {
        $demanda->load(['itens', 'equipamento', 'criador']);

        $equipamentos = Equipamento::query()
            ->whereHas('tipo', fn ($q) => $q->where('nome', 'Motorizado'))
            ->whereNotNull('prefixo')
            ->orderBy('prefixo')
            ->get(['id', 'prefixo', 'placa']);

        return view('demandas.edit', compact('demanda', 'equipamentos'));
    }

    public function modeloImportacao(ImportadorDemandas $importador): BinaryFileResponse
    {
        return response()
            ->download($importador->gerarModelo(), 'modelo-importacao-demandas.xlsx')
            ->deleteFileAfterSend();
    }

    public function importar(ImportarDemandasRequest $request, ImportadorDemandas $importador): RedirectResponse
    {
        $resultado = $importador->importar(
            $request->file('arquivo')->getRealPath(),
            auth()->id()
        );

        if ($resultado['erros'] !== []) {
            $amostra = implode(' · ', array_slice($resultado['erros'], 0, 3));
            $extras = count($resultado['erros']) > 3 ? ' (+'.(count($resultado['erros']) - 3).')' : '';

            return redirect()->route('demandas.index')
                ->with('error', "Importação concluída com pendências: {$amostra}{$extras}");
        }

        $msg = sprintf(
            '%d demanda(s) criada(s), %d item(ns) importado(s), %d atualizado(s).',
            $resultado['demandas_criadas'],
            $resultado['itens_criados'],
            $resultado['itens_atualizados']
        );

        if ($resultado['avisos'] !== []) {
            $msg .= ' '.implode(' · ', array_slice($resultado['avisos'], 0, 3));
        }

        return redirect()->route('demandas.index')->with('success', $msg);
    }

    public function export(Request $request): Response
    {
        // O export respeita exatamente os filtros aplicados na listagem.
        $demandas = $this->queryFiltrada($request, $request->input('status'))
            ->with(['equipamento', 'criador', 'itens'])
            ->when($request->input('prazo_de') || $request->input('prazo_ate'), fn ($q) => $q->orderBy('prazo_demanda'), fn ($q) => $q->latest())
            ->get();

        $fmt = fn ($dt) => $dt?->format('d/m/Y H:i') ?? '';

        $headers = [
            'Número', 'Fonte', 'Tipo', 'Tipo Cadastro',
            'Veículo (Prefixo)', 'Veículo (Placa)',
            'Documento', 'Itens', 'Origens', 'Destinos', 'Prazo',
            'Início', 'Fim', 'Ajuste Pendente',
            'Status', 'Auditado', 'Criado por', 'Cadastrado em',
        ];

        $rows = $demandas->map(fn (Demanda $d) => [
            $d->numero_demanda,
            $d->fonte_demanda?->label() ?? '',
            $d->tipo_demanda?->label() ?? '',
            $d->tipo_cadastro->label(),
            $d->equipamento?->prefixo ?? '',
            $d->equipamento?->placa ?? '',
            $d->documento_demanda ?? '',
            $d->itens->count(),
            implode(', ', $d->locaisOrigem()),
            implode(', ', $d->locaisDestino()),
            $fmt($d->prazo_demanda),
            $fmt($d->data_hora_inicio_demanda),
            $fmt($d->data_hora_fim_demanda),
            $d->inicio_automatico || $d->fim_automatico ? 'Sim' : 'Não',
            $d->status_demanda->label(),
            $d->status_auditoria ? 'Sim' : 'Não',
            $d->criador?->name ?? '',
            $fmt($d->created_at),
        ]);

        $csv = collect([$headers])
            ->concat($rows)
            ->map(fn (array $row) => implode(';', array_map(fn ($cell) => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n");

        $filename = 'demandas_'.now()->format('Y-m-d_H-i').'.csv';

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }

    public function store(StoreDemandaRequest $request): JsonResponse
    {
        $demanda = Demanda::create(array_merge(
            $request->validated(),
            [
                'tipo_cadastro' => TipoCadastro::Manual,
                'status_demanda' => StatusDemanda::Pendente,
                'criado_por' => auth()->id(),
            ]
        ));

        return response()->json(['ok' => true, 'id' => $demanda->id]);
    }

    public function update(UpdateDemandaRequest $request, Demanda $demanda, DemandaCalculadora $calculadora): JsonResponse|RedirectResponse
    {
        $dados = $request->validated();

        // Tipo escolhido no select fixa o tipo manualmente; "Automático" (vazio)
        // devolve o controle ao cálculo pelos itens.
        $dados['tipo_demanda_manual'] = $request->filled('tipo_demanda');

        // Início/fim informados pelo operador deixam de ser automáticos (a tag
        // de ajuste some); vazio devolve o horário ao cálculo automático.
        if ($request->filled('data_hora_inicio_demanda')) {
            $dados['inicio_automatico'] = false;
        }
        if (array_key_exists('data_hora_fim_demanda', $dados)) {
            $temItemPendente = $demanda->itens()
                ->where(fn ($q) => $q->whereNull('status_item')->orWhere('status_item', 'pendente'))
                ->exists();

            if ($request->filled('data_hora_fim_demanda') && $temItemPendente) {
                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'message' => 'Defina o status de todos os itens antes de informar o fim.'], 422)
                    : redirect()->route('demandas.edit', $demanda)
                        ->with('error', 'Defina o status de todos os itens antes de informar o fim da demanda.');
            }

            $dados['fim_automatico'] = ! $request->filled('data_hora_fim_demanda');
        }

        $demanda->update($dados);

        // Início/fim influenciam o status derivado; mantém tudo consistente.
        $calculadora->recalcular($demanda->load('itens'));

        // O modal da listagem envia via fetch; a página de edição usa form comum.
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('demandas.edit', $demanda)
            ->with('success', 'Demanda #'.$demanda->numero_demanda.' atualizada.');
    }

    public function auditar(Demanda $demanda): RedirectResponse
    {
        $demanda->update(['status_auditoria' => ! $demanda->status_auditoria]);

        return redirect()->back()->with('success', 'Auditoria da demanda #'.$demanda->numero_demanda.' atualizada.');
    }

    public function destroy(Demanda $demanda): RedirectResponse
    {
        abort_unless(auth()->user()->role->value === 'administrador', 403, 'Apenas administradores podem excluir demandas.');

        $demanda->delete();

        return redirect()->back()->with('success', 'Demanda #'.$demanda->numero_demanda.' removida.');
    }
}
