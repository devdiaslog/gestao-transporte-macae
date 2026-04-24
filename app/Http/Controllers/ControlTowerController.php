<?php

namespace App\Http\Controllers;

use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\EquipamentoLog;
use App\Models\ModeloEquipamento;
use App\Models\Motorista;
use App\Models\StatusOperacional;
use App\Models\TipoEquipamento;
use App\Services\VfleetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ControlTowerController extends Controller
{
    public function index(Request $request): View
    {
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();
        $tipoImplemento = TipoEquipamento::where('nome', 'Implementos')->first();

        $equipamentos = Equipamento::query()
            ->with(['modelo', 'divisao', 'implemento.modelo', 'ultimoLogOperacional', 'motorista.contatos', 'posicao'])
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->when($request->filled('modelo_id'), fn ($q) => $q->where('modelo_id', $request->modelo_id))
            ->when($request->filled('status_operacional'), fn ($q) => $q->where('status_operacional', $request->status_operacional))
            ->when($request->filled('implemento_modelo_id'), fn ($q) => $q->whereHas('implemento', fn ($q) => $q->where('modelo_id', $request->implemento_modelo_id)))
            ->when($request->filled('motorista_id'), fn ($q) => $q->where('motorista_id', $request->motorista_id))
            ->orderByRaw("(
                SELECT MAX(created_at) FROM equipamento_logs
                WHERE equipamento_id = equipamentos.id
                AND campo IN ('Status Operacional', 'Documento de Demanda')
            ) ASC")
            ->orderBy('placa')
            ->paginate(100)
            ->withQueryString();

        $divisoes = Divisao::where('status', true)->orderBy('nome')->get();

        $modelos = $tipoMotorizado
            ? ModeloEquipamento::where('tipo_equipamento_id', $tipoMotorizado->id)
                ->where('status', true)
                ->orderBy('nome')
                ->get()
            : collect();

        $modelosImplemento = $tipoImplemento
            ? ModeloEquipamento::where('tipo_equipamento_id', $tipoImplemento->id)
                ->where('status', true)
                ->orderBy('nome')
                ->get()
            : collect();

        // All active implementos with their modelo, plus which motorizado (if any) has linked them
        $takenImplementoIds = Equipamento::whereNotNull('implemento_id')
            ->pluck('implemento_id', 'id'); // motorizado_id => implemento_id

        $implementos = $tipoImplemento
            ? Equipamento::where('tipo_id', $tipoImplemento->id)
                ->where('status', true)
                ->with('modelo')
                ->orderBy('placa')
                ->get()
                ->map(function (Equipamento $imp) use ($takenImplementoIds) {
                    $takenByMotorizadoId = $takenImplementoIds->search($imp->id);

                    return [
                        'id' => $imp->id,
                        'placa' => $imp->placa,
                        'prefixo' => $imp->prefixo,
                        'modelo' => $imp->modelo?->nome,
                        'taken_by' => $takenByMotorizadoId ?: null,
                    ];
                })
            : collect();

        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();
        $statusCores = $statusOperacionais->pluck('cor', 'nome');

        $motoristas = Motorista::where('status', true)->orderBy('nome')->get();

        // motorista_id => equipamento_id para marcar "em uso" no select
        $motoristaOcupado = Equipamento::whereNotNull('motorista_id')
            ->pluck('id', 'motorista_id'); // motorista_id => equipamento_id

        return view('control-tower.index', compact('equipamentos', 'divisoes', 'modelos', 'modelosImplemento', 'implementos', 'statusOperacionais', 'statusCores', 'motoristas', 'motoristaOcupado'));
    }

    public function posicao(string $plate, VfleetsService $vfleets): JsonResponse
    {
        try {
            $pos = $vfleets->sincronizarPlaca($plate);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()], 500);
        }

        if (! $pos || ! $pos->latitude || ! $pos->longitude) {
            return response()->json(['ok' => false, 'erro' => 'Sem localização disponível.'], 404);
        }

        return response()->json([
            'ok' => true,
            'latitude' => $pos->latitude,
            'longitude' => $pos->longitude,
            'position_at' => $pos->position_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
            'synced_at' => $pos->synced_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
        ]);
    }

    public function historico(Request $request, Equipamento $equipamento): View
    {
        $campos = EquipamentoLog::where('equipamento_id', $equipamento->id)
            ->distinct()
            ->orderBy('campo')
            ->pluck('campo');

        $logs = EquipamentoLog::query()
            ->with('user')
            ->where('equipamento_id', $equipamento->id)
            ->when($request->filled('campo'), fn ($q) => $q->where('campo', $request->campo))
            ->when($request->filled('data'), fn ($q) => $q->whereDate('created_at', $request->data))
            ->when($request->filled('documento'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('valor_anterior', 'like', '%'.$request->documento.'%')
                    ->orWhere('valor_novo', 'like', '%'.$request->documento.'%');
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('control-tower.historico', compact('equipamento', 'logs', 'campos'));
    }

    public function updateImplemento(Request $request, Equipamento $equipamento): RedirectResponse
    {
        $validated = $request->validate([
            'implemento_id' => ['nullable', 'integer', 'exists:equipamentos,id'],
            'implemento_nome_override' => ['nullable', 'string', 'max:255'],
        ]);

        // Ensure the chosen implemento is not already linked to another motorizado
        if (! empty($validated['implemento_id'])) {
            $alreadyTaken = Equipamento::where('implemento_id', $validated['implemento_id'])
                ->where('id', '!=', $equipamento->id)
                ->exists();

            if ($alreadyTaken) {
                return redirect()->back()->withErrors(['implemento_id' => 'Este implemento já está vinculado a outro motorizado.']);
            }
        }

        // Clear override when unlinking
        if (empty($validated['implemento_id'])) {
            $validated['implemento_nome_override'] = null;
        }

        $equipamento->update($validated);

        return redirect()->back()->with('success', 'Implemento atualizado com sucesso.');
    }
}
