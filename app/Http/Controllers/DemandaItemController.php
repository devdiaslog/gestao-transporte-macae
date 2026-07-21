<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDemandaItemRequest;
use App\Models\DemandaItem;
use App\Services\DemandaCalculadora;
use Illuminate\Http\RedirectResponse;

class DemandaItemController extends Controller
{
    public function __construct(private DemandaCalculadora $calculadora) {}

    public function update(UpdateDemandaItemRequest $request, DemandaItem $item): RedirectResponse
    {
        $item->update($request->validated());

        // Origem, destino, status e prazo do item alimentam os campos derivados.
        $this->calculadora->recalcular($item->demanda->load('itens'));

        return redirect()
            ->route('demandas.edit', $item->demanda_id)
            ->with('success', "Item {$item->numero_rt} / {$item->numero_item} atualizado.");
    }

    public function destroy(DemandaItem $item): RedirectResponse
    {
        $demanda = $item->demanda;
        $identificacao = "{$item->numero_rt} / {$item->numero_item}";

        $item->delete();

        $this->calculadora->recalcular($demanda->load('itens'));

        return redirect()
            ->route('demandas.edit', $demanda->id)
            ->with('success', "Item {$identificacao} removido.");
    }
}
