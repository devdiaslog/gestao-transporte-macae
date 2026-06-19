<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinalizeEtapaRequest;
use App\Http\Requests\StoreEtapaRequest;
use App\Http\Requests\UpdateEtapaRequest;
use App\Models\Equipamento;
use App\Models\Etapa;
use App\Models\LocalEtapa;
use App\Models\Motorista;
use App\Models\TipoEtapa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EtapaController extends Controller
{
    public function veiculo(Request $request, Equipamento $equipamento): View
    {
        $etapas = Etapa::query()
            ->with(['tipoEtapa', 'localEtapa', 'motorista', 'emissor', 'finalizador', 'auditor'])
            ->where('equipamento_id', $equipamento->id)
            ->when($request->input('status') === 'finalizado', fn ($q) => $q->whereNotNull('data_hora_fim'))
            ->when($request->input('status') === 'aberto', fn ($q) => $q->whereNull('data_hora_fim'))
            ->when($request->filled('tipo_etapa_id'), fn ($q) => $q->where('tipo_etapa_id', $request->tipo_etapa_id))
            ->when($request->filled('data_inicio'), fn ($q) => $q->whereDate('data_hora_inicio', '>=', Carbon::parse($request->data_inicio)))
            ->when($request->filled('data_fim'), fn ($q) => $q->whereDate('data_hora_inicio', '<=', Carbon::parse($request->data_fim)))
            ->when($request->filled('documento'), fn ($q) => $q->where('documento', 'like', '%'.$request->documento.'%'))
            ->latest('data_hora_inicio')
            ->paginate(15)
            ->withQueryString();

        $tipos = TipoEtapa::where('ativo', true)->orderBy('nome')->get();
        $locais = LocalEtapa::where('ativo', true)->orderBy('nome')->get();
        $motoristas = Motorista::where('status', true)->orderBy('nome')->get();

        $ultimaEtapa = Etapa::query()
            ->with(['localEtapa', 'motorista'])
            ->where('equipamento_id', $equipamento->id)
            ->latest('data_hora_inicio')
            ->first();

        return view('etapas.veiculo', compact('equipamento', 'etapas', 'tipos', 'locais', 'motoristas', 'ultimaEtapa'));
    }

    public function store(StoreEtapaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['emitido_por'] = auth()->id();

        if (! empty($data['data_hora_fim'])) {
            $data['finalizado_por'] = auth()->id();
        }

        Etapa::create($data);

        return redirect()->back()->with('success', 'Etapa registrada com sucesso.');
    }

    public function finalize(FinalizeEtapaRequest $request, Etapa $etapa): RedirectResponse
    {
        DB::transaction(function () use ($request, $etapa): void {
            $etapa->update([
                'data_hora_fim' => $request->data_hora_fim,
                'finalizado_por' => auth()->id(),
                'motivo_longa_duracao' => $request->motivo_longa_duracao ?: null,
            ]);

            Etapa::create([
                'equipamento_id' => $etapa->equipamento_id,
                'tipo_etapa_id' => $request->proxima_tipo_etapa_id,
                'local_etapa_id' => $request->proxima_local_etapa_id,
                'motorista_id' => $request->proxima_motorista_id ?: null,
                'documento' => $request->proxima_documento ?: null,
                'data_hora_inicio' => $request->proxima_data_hora_inicio,
                'observacao' => $request->proxima_observacao ?: null,
                'emitido_por' => auth()->id(),
            ]);
        });

        return redirect()->back()->with('success', 'Etapa finalizada e próxima etapa registrada com sucesso.');
    }

    public function update(UpdateEtapaRequest $request, Etapa $etapa): RedirectResponse
    {
        $data = $request->validated();

        $hadFim = ! is_null($etapa->data_hora_fim);
        $hasFim = ! empty($data['data_hora_fim']);

        if ($hasFim && ! $hadFim) {
            $data['finalizado_por'] = auth()->id();
        } elseif (! $hasFim) {
            $data['finalizado_por'] = null;
        }

        $etapa->update($data);

        return redirect()->back()->with('success', 'Etapa atualizada com sucesso.');
    }

    public function destroy(Etapa $etapa): RedirectResponse
    {
        $etapa->delete();

        return redirect()->back()->with('success', 'Etapa removida com sucesso.');
    }
}
