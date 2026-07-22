<?php

namespace App\Http\Controllers;

use App\Enums\StatusItemDemanda;
use App\Http\Requests\AtualizarEntregaEtapaRequest;
use App\Http\Requests\AtualizarStatusEtapaRequest;
use App\Http\Requests\ImportarDemandasRequest;
use App\Http\Requests\StoreDemandaItemRequest;
use App\Http\Requests\UpdateDemandaItemRequest;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Services\DemandaCalculadora;
use App\Services\ImportadorDemandas;
use Illuminate\Http\RedirectResponse;

class DemandaItemController extends Controller
{
    public function __construct(private DemandaCalculadora $calculadora) {}

    /**
     * Cadastra manualmente um novo item na demanda (fallback quando a
     * importação em lote falha ou já foi feita).
     */
    public function store(StoreDemandaItemRequest $request, Demanda $demanda): RedirectResponse
    {
        $item = $demanda->itens()->create($request->validated());

        $this->calculadora->recalcular($demanda->load('itens'));

        return redirect()
            ->route('demandas.edit', $demanda)
            ->with('success', "Item {$item->numero_rt} / {$item->numero_item} adicionado.");
    }

    /**
     * Importa itens de uma planilha apenas para esta demanda (escopado à Nota).
     */
    public function importar(ImportarDemandasRequest $request, Demanda $demanda, ImportadorDemandas $importador): RedirectResponse
    {
        $resultado = $importador->importar(
            $request->file('arquivo')->getRealPath(),
            auth()->id(),
            $demanda->numero_demanda,
        );

        if ($resultado['erros'] !== []) {
            $amostra = implode(' · ', array_slice($resultado['erros'], 0, 3));

            return redirect()->route('demandas.edit', $demanda)->with('error', $amostra);
        }

        if ($resultado['itens_criados'] === 0 && $resultado['itens_atualizados'] === 0) {
            return redirect()->route('demandas.edit', $demanda)
                ->with('error', "A planilha não tinha itens para a demanda #{$demanda->numero_demanda}.");
        }

        $msg = sprintf(
            '%d item(ns) importado(s), %d atualizado(s).',
            $resultado['itens_criados'],
            $resultado['itens_atualizados'],
        );

        if ($resultado['avisos'] !== []) {
            $msg .= ' '.implode(' · ', array_slice($resultado['avisos'], 0, 3));
        }

        return redirect()->route('demandas.edit', $demanda)->with('success', $msg);
    }

    /**
     * Redireciona de volta com erro quando a demanda não permite alterar itens.
     */
    private function bloqueio(Demanda $demanda): ?RedirectResponse
    {
        if ($motivo = $demanda->loadMissing('itens')->motivoBloqueioItens()) {
            return redirect()->route('demandas.edit', $demanda)->with('error', $motivo);
        }

        return null;
    }

    /**
     * Define o mesmo status para todos os itens de uma etapa da demanda.
     */
    public function atualizarStatusEtapa(AtualizarStatusEtapaRequest $request, Demanda $demanda): RedirectResponse
    {
        if ($bloqueio = $this->bloqueio($demanda)) {
            return $bloqueio;
        }

        $status = StatusItemDemanda::from($request->input('status_item'));

        $afetados = $demanda->itens()
            ->whereIn('id', $request->input('itens'))
            ->update(['status_item' => $status->value]);

        $this->calculadora->recalcular($demanda->load('itens'));

        return redirect()
            ->route('demandas.edit', $demanda)
            ->with('success', "{$afetados} item(ns) da etapa marcados como {$status->label()}.");
    }

    /**
     * Define a mesma data/hora de entrega para todos os itens de uma etapa.
     */
    public function atualizarEntregaEtapa(AtualizarEntregaEtapaRequest $request, Demanda $demanda): RedirectResponse
    {
        if ($bloqueio = $this->bloqueio($demanda)) {
            return $bloqueio;
        }

        $afetados = $demanda->itens()
            ->whereIn('id', $request->input('itens'))
            ->update(['data_hora_entrega' => $request->date('data_hora_entrega')]);

        return redirect()
            ->route('demandas.edit', $demanda)
            ->with('success', "Data de entrega aplicada a {$afetados} item(ns) da etapa.");
    }

    public function update(UpdateDemandaItemRequest $request, DemandaItem $item): RedirectResponse
    {
        $demanda = $item->demanda;

        if ($bloqueio = $this->bloqueio($demanda)) {
            return $bloqueio;
        }

        $item->fill($request->validated());

        // Campos mestres alterados pelo operador passam a ser dele: a
        // importação do SAP não volta a sincronizá-los neste item.
        $editados = array_values(array_unique(array_merge(
            $item->campos_editados ?? [],
            array_intersect(array_keys($item->getDirty()), DemandaItem::CAMPOS_MESTRES),
        )));
        $item->campos_editados = $editados === [] ? null : $editados;
        $item->save();

        // Origem, destino, status e prazo do item alimentam os campos derivados.
        $this->calculadora->recalcular($demanda->load('itens'));

        return redirect()
            ->route('demandas.edit', $demanda)
            ->with('success', "Item {$item->numero_rt} / {$item->numero_item} atualizado.");
    }

    public function destroy(DemandaItem $item): RedirectResponse
    {
        $demanda = $item->demanda;

        if ($bloqueio = $this->bloqueio($demanda)) {
            return $bloqueio;
        }

        $identificacao = "{$item->numero_rt} / {$item->numero_item}";

        $item->delete();

        $this->calculadora->recalcular($demanda->load('itens'));

        return redirect()
            ->route('demandas.edit', $demanda)
            ->with('success', "Item {$identificacao} removido.");
    }
}
