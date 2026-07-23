<?php

namespace App\Http\Controllers;

use App\Enums\StatusAuditoria;
use App\Enums\UserRole;
use App\Http\Requests\StoreOcorrenciaRequest;
use App\Http\Requests\UpdateOcorrenciaRequest;
use App\Models\Equipamento;
use App\Models\Justificativa;
use App\Models\Ocorrencia;
use App\Models\Responsavel;
use App\Models\TipoEquipamento;
use App\Models\TipoOcorrencia;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class OcorrenciaController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('demandas.index');
    }

    public function create(): View
    {
        [$veiculos, $tipos, $responsaveis, $justificativas, $tiposMap, $responsaveisMap] = $this->formData();

        return view('ocorrencias.create', compact('veiculos', 'tipos', 'responsaveis', 'justificativas', 'tiposMap', 'responsaveisMap'));
    }

    public function store(StoreOcorrenciaRequest $request): RedirectResponse
    {
        Ocorrencia::create(array_merge($request->validated(), [
            'created_by' => auth()->id(),
        ]));

        return redirect()->back()
            ->with('success', 'Ocorrência registrada com sucesso.');
    }

    public function edit(Ocorrencia $ocorrencia): View
    {
        [$veiculos, $tipos, $responsaveis, $justificativas, $tiposMap, $responsaveisMap] = $this->formData();

        return view('ocorrencias.edit', compact('ocorrencia', 'veiculos', 'tipos', 'responsaveis', 'justificativas', 'tiposMap', 'responsaveisMap'));
    }

    public function update(UpdateOcorrenciaRequest $request, Ocorrencia $ocorrencia): RedirectResponse
    {
        $ocorrencia->update($request->validated());

        return redirect()->back()
            ->with('success', 'Ocorrência atualizada com sucesso.');
    }

    public function export(Request $request): Response
    {
        $ocorrencias = Ocorrencia::query()
            ->with(['veiculo', 'tipo', 'responsavel', 'justificativa'])
            ->when($request->filled('id_veiculo'), fn ($q) => $q->where('id_veiculo', $request->id_veiculo))
            ->when($request->filled('id_tipo'), fn ($q) => $q->where('id_tipo', $request->id_tipo))
            ->when($request->filled('id_responsavel'), fn ($q) => $q->where('id_responsavel', $request->id_responsavel))
            ->when($request->input('status') === 'aberta', fn ($q) => $q->whereNull('data_hora_fim'))
            ->when($request->input('status') === 'fechada', fn ($q) => $q->whereNotNull('data_hora_fim'))
            ->when($request->filled('data_inicio'), fn ($q) => $q->whereDate('data_hora_inicio', '>=', Carbon::parse($request->data_inicio)))
            ->when($request->filled('data_fim'), fn ($q) => $q->whereDate('data_hora_inicio', '<=', Carbon::parse($request->data_fim)))
            ->latest('data_hora_inicio')
            ->get();

        $filename = 'ocorrencias_'.now()->format('Y-m-d_H-i').'.csv';

        $csv = collect([['Veículo', 'Prefixo', 'Tipo', 'Status', 'Responsável', 'Justificativa', 'Início', 'Fim', 'Documento', 'Observação']])
            ->concat($ocorrencias->map(fn (Ocorrencia $ocorrencia) => [
                $ocorrencia->veiculo?->placa ?? '',
                $ocorrencia->veiculo?->prefixo ?? '',
                $ocorrencia->tipo?->descricao ?? '',
                $ocorrencia->status_ocorrencia,
                $ocorrencia->responsavel?->nome ?? '',
                $ocorrencia->justificativa?->descricao ?? '',
                $ocorrencia->data_hora_inicio->format('d/m/Y H:i'),
                $ocorrencia->data_hora_fim?->format('d/m/Y H:i') ?? '',
                $ocorrencia->documento ?? '',
                $ocorrencia->observacao ?? '',
            ]))
            ->map(fn (array $row) => implode(';', array_map(fn ($cell) => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n");

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }

    public function veiculo(Request $request, Equipamento $equipamento): View
    {
        $ocorrencias = Ocorrencia::query()
            ->with(['tipo', 'responsavel', 'justificativa', 'creator', 'auditor'])
            ->where('id_veiculo', $equipamento->id)
            ->when(! $request->has('status') || $request->status === 'aberta', fn ($q) => $q->whereNull('data_hora_fim'))
            ->when($request->has('status') && $request->status === 'fechada', fn ($q) => $q->whereNotNull('data_hora_fim'))
            ->when($request->filled('id_tipo'), fn ($q) => $q->where('id_tipo', $request->id_tipo))
            ->when($request->filled('data_inicio'), fn ($q) => $q->whereDate('data_hora_inicio', '>=', Carbon::parse($request->data_inicio)))
            ->when($request->filled('data_fim'), fn ($q) => $q->whereDate('data_hora_inicio', '<=', Carbon::parse($request->data_fim)))
            ->latest('data_hora_inicio')
            ->paginate(15)
            ->withQueryString();

        [, $tipos, $responsaveis, $justificativas, $tiposMap, $responsaveisMap] = $this->formData();

        return view('ocorrencias.veiculo', compact('equipamento', 'ocorrencias', 'tipos', 'responsaveis', 'justificativas', 'tiposMap', 'responsaveisMap'));
    }

    public function auditar(Request $request, Ocorrencia $ocorrencia): RedirectResponse
    {
        Gate::authorize('auditar-ocorrencias');

        if (is_null($ocorrencia->data_hora_fim)) {
            abort(422, 'Só é possível auditar ocorrências fechadas.');
        }

        $request->validate([
            'status_auditoria' => ['required', new Enum(StatusAuditoria::class)],
            'observacao_auditoria' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = StatusAuditoria::from($request->status_auditoria);

        if ($status === StatusAuditoria::Reprovada && blank($request->observacao_auditoria)) {
            return back()->withErrors(['observacao_auditoria' => 'Informe o motivo da reprovação.']);
        }

        $ocorrencia->update([
            'status_auditoria' => $status,
            'auditado_por' => $status === StatusAuditoria::Pendente ? null : auth()->id(),
            'auditado_em' => $status === StatusAuditoria::Pendente ? null : now(),
            'observacao_auditoria' => $status === StatusAuditoria::Pendente ? null : $request->observacao_auditoria,
        ]);

        return redirect()->back()->with('success', 'Auditoria registrada com sucesso.');
    }

    public function destroy(Ocorrencia $ocorrencia): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->role === UserRole::Operador && $ocorrencia->created_by !== $user->id) {
            abort(403, 'Você só pode remover ocorrências registradas por você.');
        }

        $ocorrencia->delete();

        return redirect()->back()
            ->with('success', 'Ocorrência removida com sucesso.');
    }

    /** @return array<int, mixed> */
    private function formData(): array
    {
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();

        $veiculos = Equipamento::query()
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->orderBy('placa')
            ->get();

        $tipos = TipoOcorrencia::with('justificativas', 'responsaveis')->orderBy('descricao')->get();
        $responsaveis = Responsavel::where('ativo', true)->orderBy('nome')->get();
        $justificativas = Justificativa::where('ativo', true)->orderBy('descricao')->get();

        /** @var array<int|string, int[]> $tiposMap */
        $tiposMap = $tipos->mapWithKeys(
            fn ($tipo) => [$tipo->id_tipo => $tipo->justificativas->pluck('id_justificativa')->toArray()]
        )->toArray();

        /** @var array<int|string, int[]> $responsaveisMap */
        $responsaveisMap = $tipos->mapWithKeys(
            fn ($tipo) => [$tipo->id_tipo => $tipo->responsaveis->pluck('id_responsavel')->toArray()]
        )->toArray();

        return [$veiculos, $tipos, $responsaveis, $justificativas, $tiposMap, $responsaveisMap];
    }
}
