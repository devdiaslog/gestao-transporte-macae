<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlertaRequest;
use App\Http\Requests\UpdateAlertaRequest;
use App\Models\Alerta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AlertaController extends Controller
{
    public function index(Request $request): View
    {
        $escopo = $request->input('escopo', 'todos'); // todos | meus
        $status = $request->input('status', 'pendentes'); // pendentes | resolvidos | todos

        $alertas = Alerta::query()
            ->with(['equipamento', 'criador', 'resolvedor'])
            ->visivelPara(auth()->id())
            ->when($escopo === 'meus', fn ($q) => $q->where('criado_por', auth()->id()))
            ->when($status === 'pendentes', fn ($q) => $q->where('status', 'pendente'))
            ->when($status === 'resolvidos', fn ($q) => $q->where('status', 'resolvido'))
            ->orderByRaw("CASE WHEN status = 'pendente' THEN 0 ELSE 1 END")
            ->orderBy('data_hora_alerta')
            ->paginate(20)
            ->withQueryString();

        return view('alertas.index', compact('alertas', 'escopo', 'status'));
    }

    public function store(StoreAlertaRequest $request): JsonResponse
    {
        $alerta = Alerta::create([
            'equipamento_id' => $request->validated('equipamento_id'),
            'criado_por' => auth()->id(),
            'lembrete' => $request->validated('lembrete'),
            'tipo' => 'tempo',
            'data_hora_alerta' => $request->validated('data_hora_alerta'),
            'para_todos' => $request->boolean('para_todos'),
        ]);

        return response()->json(['ok' => true, 'id' => $alerta->id]);
    }

    public function update(UpdateAlertaRequest $request, Alerta $alerta): RedirectResponse
    {
        $this->autorizarGerenciar($alerta);

        $alerta->update([
            'lembrete' => $request->validated('lembrete'),
            'data_hora_alerta' => $request->validated('data_hora_alerta'),
            'para_todos' => $request->boolean('para_todos'),
        ]);

        return redirect()->back()->with('success', 'Alerta atualizado com sucesso.');
    }

    public function prorrogar(Request $request, Alerta $alerta): RedirectResponse
    {
        $this->autorizarGerenciar($alerta);

        $validated = $request->validate([
            'minutos' => ['nullable', 'integer', 'min:1', 'max:43200'],
            'data_hora_alerta' => ['nullable', 'date', 'after:now'],
        ]);

        $novaData = ! empty($validated['data_hora_alerta'])
            ? Carbon::parse($validated['data_hora_alerta'])
            : Carbon::now()->addMinutes((int) ($validated['minutos'] ?? 60));

        $alerta->update([
            'data_hora_alerta' => $novaData,
            'status' => 'pendente',
            'resolvido_por' => null,
            'resolvido_em' => null,
        ]);

        return redirect()->back()->with('success', 'Alerta prorrogado para '.$novaData->format('d/m/Y H:i').'.');
    }

    public function resolver(Alerta $alerta): RedirectResponse
    {
        abort_unless($this->podeVer($alerta), 403);

        $alerta->update([
            'status' => 'resolvido',
            'resolvido_por' => auth()->id(),
            'resolvido_em' => now(),
        ]);

        return redirect()->back()->with('success', 'Alerta resolvido.');
    }

    public function destroy(Alerta $alerta): RedirectResponse
    {
        $this->autorizarGerenciar($alerta);

        $alerta->delete();

        return redirect()->back()->with('success', 'Alerta removido.');
    }

    /** Endpoint consumido pelo sino do header (polling). */
    public function pendentes(): JsonResponse
    {
        $tz = config('app.timezone');

        $alertas = Alerta::query()
            ->with('equipamento')
            ->visivelPara(auth()->id())
            ->where('status', 'pendente')
            ->whereNotNull('data_hora_alerta')
            ->where('data_hora_alerta', '<=', now()->addHours(24))
            ->orderBy('data_hora_alerta')
            ->limit(30)
            ->get();

        $disparados = 0;

        $itens = $alertas->map(function (Alerta $a) use ($tz, &$disparados): array {
            $disparado = $a->data_hora_alerta->isPast();
            if ($disparado) {
                $disparados++;
            }

            return [
                'id' => $a->getRouteKey(),
                'lembrete' => $a->lembrete,
                'veiculo' => trim(($a->equipamento?->prefixo ?? '').' '.($a->equipamento?->placa ?? '')),
                'data_hora' => $a->data_hora_alerta->setTimezone($tz)->format('d/m/Y H:i'),
                'disparado' => $disparado,
            ];
        });

        return response()->json([
            'ok' => true,
            'disparados' => $disparados,
            'total' => $itens->count(),
            'alertas' => $itens,
        ]);
    }

    private function podeVer(Alerta $alerta): bool
    {
        return $alerta->para_todos || $alerta->criado_por === auth()->id();
    }

    private function autorizarGerenciar(Alerta $alerta): void
    {
        abort_unless($alerta->criado_por === auth()->id(), 403, 'Apenas quem criou o alerta pode gerenciá-lo.');
    }
}
