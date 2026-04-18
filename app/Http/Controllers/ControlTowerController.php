<?php

namespace App\Http\Controllers;

use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\ModeloEquipamento;
use App\Models\TipoEquipamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ControlTowerController extends Controller
{
    public function index(Request $request): View
    {
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();
        $tipoImplemento = TipoEquipamento::where('nome', 'Implementos')->first();

        $equipamentos = Equipamento::query()
            ->with(['modelo', 'divisao', 'implemento.modelo'])
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->when($request->filled('modelo_id'), fn ($q) => $q->where('modelo_id', $request->modelo_id))
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

        return view('control-tower.index', compact('equipamentos', 'divisoes', 'modelos', 'implementos'));
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
