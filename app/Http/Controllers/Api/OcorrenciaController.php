<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ocorrencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OcorrenciaController extends Controller
{
    /**
     * Lista ocorrências com os mesmos filtros da view web.
     *
     * Filtros disponíveis (query string):
     *   id_veiculo        — ID do equipamento
     *   id_tipo           — ID do tipo de ocorrência
     *   id_responsavel    — ID do responsável
     *   status            — "aberta" (padrão) | "fechada" | "todas"
     *   status_auditoria  — pendente | aprovada | reprovada
     *   data_inicio       — Y-m-d
     *   data_fim          — Y-m-d
     *   per_page          — itens por página (padrão 50, máx 200)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 50), 200);

        $ocorrencias = Ocorrencia::query()
            ->with(['veiculo', 'tipo', 'responsavel', 'justificativa', 'creator', 'auditor'])
            ->when($request->filled('id_veiculo'), fn ($q) => $q->where('id_veiculo', $request->id_veiculo))
            ->when($request->filled('id_tipo'), fn ($q) => $q->where('id_tipo', $request->id_tipo))
            ->when($request->filled('id_responsavel'), fn ($q) => $q->where('id_responsavel', $request->id_responsavel))
            ->when($request->input('status', 'aberta') === 'aberta', fn ($q) => $q->whereNull('data_hora_fim'))
            ->when($request->input('status') === 'fechada', fn ($q) => $q->whereNotNull('data_hora_fim'))
            ->when($request->filled('status_auditoria'), fn ($q) => $q->where('status_auditoria', $request->status_auditoria))
            ->when($request->filled('data_inicio'), fn ($q) => $q->whereDate('data_hora_inicio', '>=', Carbon::parse($request->data_inicio)))
            ->when($request->filled('data_fim'), fn ($q) => $q->whereDate('data_hora_inicio', '<=', Carbon::parse($request->data_fim)))
            ->latest('data_hora_inicio')
            ->paginate($perPage);

        $tz = config('app.timezone');

        $items = $ocorrencias->getCollection()->map(fn (Ocorrencia $o) => [
            'id' => $o->id,
            'veiculo_placa' => $o->veiculo?->placa,
            'veiculo_prefixo' => $o->veiculo?->prefixo,
            'tipo' => $o->tipo?->nome,
            'responsavel' => $o->responsavel?->nome,
            'data_hora_inicio' => $o->data_hora_inicio?->setTimezone($tz)->format('d/m/Y H:i'),
            'data_hora_fim' => $o->data_hora_fim?->setTimezone($tz)->format('d/m/Y H:i'),
            'status' => $o->data_hora_fim ? 'fechada' : 'aberta',
            'status_auditoria' => $o->status_auditoria,
            'justificativa' => $o->justificativa?->descricao,
            'observacao' => $o->observacao,
            'observacao_auditoria' => $o->observacao_auditoria,
            'criado_por' => $o->creator?->name,
            'auditado_por' => $o->auditor?->name,
            'created_at' => $o->created_at?->setTimezone($tz)->format('d/m/Y H:i'),
        ]);

        return response()->json([
            'data' => $items,
            'total' => $ocorrencias->total(),
            'per_page' => $ocorrencias->perPage(),
            'current_page' => $ocorrencias->currentPage(),
            'last_page' => $ocorrencias->lastPage(),
        ]);
    }
}
