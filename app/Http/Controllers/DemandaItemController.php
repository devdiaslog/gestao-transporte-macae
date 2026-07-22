<?php

namespace App\Http\Controllers;

use App\Enums\StatusItemDemanda;
use App\Http\Requests\AtualizarEntregaEtapaRequest;
use App\Http\Requests\AtualizarStatusEtapaRequest;
use App\Http\Requests\UpdateDemandaItemRequest;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Services\DemandaCalculadora;
use Illuminate\Http\RedirectResponse;

class DemandaItemController extends Controller
{
    public function __construct(private DemandaCalculadora $calculadora) {}

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

        $item->update($request->validated());

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
