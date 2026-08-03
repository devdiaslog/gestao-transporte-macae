<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListarDemandasApiRequest;
use App\Models\Demanda;
use Illuminate\Http\JsonResponse;

class DemandaConsultaController extends Controller
{
    /**
     * Lista as demandas (atendimentos) para a automação decidir o que buscar
     * no SAP, em vez de reenviar o acervo inteiro a cada rodada.
     *
     * Filtros (query string, todos opcionais):
     *   fonte[]     — sap_lt | sap_tm (aceita também "sap_lt,sap_tm")
     *   status[]    — pendente | em_andamento | finalizado | cancelada | recusa | suspensa
     *   sem_itens   — true devolve só as demandas que ainda não têm item
     *   per_page    — padrão 100, máximo 500
     */
    public function index(ListarDemandasApiRequest $request): JsonResponse
    {
        $perPage = (int) $request->validated('per_page', 100);

        $demandas = Demanda::query()
            ->withCount('itens')
            ->when($request->validated('fonte'), fn ($q, $fontes) => $q->whereIn('fonte_demanda', $fontes))
            ->when($request->validated('status'), fn ($q, $status) => $q->whereIn('status_demanda', $status))
            ->when($request->boolean('sem_itens'), fn ($q) => $q->whereDoesntHave('itens'))
            ->orderBy('numero_demanda')
            ->paginate($perPage);

        $tz = config('app.timezone');

        $items = $demandas->getCollection()->map(fn (Demanda $d) => [
            'numero_demanda' => $d->numero_demanda,
            'fonte_demanda' => $d->fonte_demanda?->value,
            'status_demanda' => $d->status_demanda?->value,
            'tipo_demanda' => $d->tipo_demanda?->value,
            'documento_demanda' => $d->documento_demanda,
            'total_itens' => $d->itens_count,
            'data_hora_criacao_sap' => $d->data_hora_criacao_sap?->setTimezone($tz)->format('d.m.Y H:i:s'),
            'prazo_demanda' => $d->prazo_demanda?->setTimezone($tz)->format('d.m.Y H:i:s'),
            'data_hora_inicio_demanda' => $d->data_hora_inicio_demanda?->setTimezone($tz)->format('d.m.Y H:i:s'),
            'data_hora_fim_demanda' => $d->data_hora_fim_demanda?->setTimezone($tz)->format('d.m.Y H:i:s'),
        ]);

        return response()->json([
            'data' => $items,
            'total' => $demandas->total(),
            'per_page' => $demandas->perPage(),
            'current_page' => $demandas->currentPage(),
            'last_page' => $demandas->lastPage(),
        ]);
    }
}
