<?php

namespace App\Http\Controllers;

use App\Enums\PrazoPadrao;
use App\Http\Requests\ImportarTrechosSapRequest;
use App\Http\Requests\StoreTrechoSapRequest;
use App\Http\Requests\UpdateTrechoSapRequest;
use App\Models\TrechoSap;
use App\Services\ImportadorTrechosSap;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Tabela de apoio com o prazo acordado de cada trecho origem→destino.
 *
 * É a referência que diz quanto tempo um item tem para percorrer a rota,
 * alimentando a cobrança de prazo nas telas de demanda e de itens.
 */
class TrechoSapController extends Controller
{
    public function index(Request $request): View
    {
        $trechos = TrechoSap::query()
            ->busca($request->input('busca'))
            ->preenchimento($request->input('preenchimento'))
            ->when($request->filled('prazo_padrao'), fn ($q) => $q->where('prazo_padrao', $request->input('prazo_padrao')))
            ->orderBy('origem_sap')
            ->orderBy('destino_sap')
            ->paginate(25)
            ->withQueryString();

        return view('trechos-sap.index', [
            'trechos' => $trechos,
            'prazosPadrao' => PrazoPadrao::cases(),
            'aPreencher' => TrechoSap::query()->preenchimento('incompletos')->count(),
            'total' => TrechoSap::count(),
            'filtros' => $request->only(['busca', 'prazo_padrao', 'preenchimento']),
        ]);
    }

    public function store(StoreTrechoSapRequest $request): RedirectResponse
    {
        $trecho = new TrechoSap($request->validated());
        $trecho->atualizado_por = $request->user()->id;
        $trecho->save();

        return redirect()
            ->route('trechos-sap.index')
            ->with('success', "Trecho {$trecho->origem_sap} → {$trecho->destino_sap} cadastrado.");
    }

    public function update(UpdateTrechoSapRequest $request, TrechoSap $trechosSap): RedirectResponse
    {
        $trechosSap->fill($request->validated());
        $trechosSap->atualizado_por = $request->user()->id;
        $trechosSap->save();

        return redirect()
            ->route('trechos-sap.index')
            ->with('success', "Trecho {$trechosSap->origem_sap} → {$trechosSap->destino_sap} atualizado.");
    }

    public function destroy(TrechoSap $trechosSap): RedirectResponse
    {
        $nome = "{$trechosSap->origem_sap} → {$trechosSap->destino_sap}";
        $trechosSap->delete();

        return redirect()->route('trechos-sap.index')->with('success', "Trecho {$nome} removido.");
    }

    /**
     * Importa a planilha de prazos.
     *
     * O arquivo é recusado por inteiro quando a mesma rota aparece com
     * quilometragem ou prazo diferentes: aplicar um dos valores em silêncio
     * daria prazo errado ao item.
     */
    public function importar(ImportarTrechosSapRequest $request, ImportadorTrechosSap $importador): RedirectResponse
    {
        $resultado = $importador->importar(
            $request->file('arquivo')->getRealPath(),
            $request->user()->id,
        );

        if ($resultado['conflitos'] !== []) {
            return back()
                ->with('error', 'Nada foi importado: a planilha traz a mesma rota com valores diferentes.')
                ->with('conflitos', $resultado['conflitos']);
        }

        if ($resultado['erros'] !== []) {
            return back()->with('error', implode(' ', $resultado['erros']));
        }

        return back()->with('success', sprintf(
            '%d trecho(s) criado(s), %d atualizado(s), %d sem alteração.',
            $resultado['criados'],
            $resultado['atualizados'],
            $resultado['inalterados'],
        ));
    }

    public function modeloImportacao(ImportadorTrechosSap $importador): BinaryFileResponse
    {
        return response()
            ->download($importador->gerarModelo(), 'modelo-trechos-sap.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Exporta o recorte atual em CSV.
     */
    public function export(Request $request): Response
    {
        $trechos = TrechoSap::query()
            ->busca($request->input('busca'))
            ->preenchimento($request->input('preenchimento'))
            ->when($request->filled('prazo_padrao'), fn ($q) => $q->where('prazo_padrao', $request->input('prazo_padrao')))
            ->orderBy('origem_sap')
            ->orderBy('destino_sap')
            ->get();

        $linhas = [['Origem SAP', 'Destino SAP', 'Cidade Origem', 'Cidade Destino', 'Chave', 'Distância (km)', 'Prazo Hora Normal', 'Prazo Hora Expresso', 'Prazo padrão']];

        foreach ($trechos as $t) {
            $linhas[] = [
                $t->origem_sap,
                $t->destino_sap,
                $t->cidade_origem,
                $t->cidade_destino,
                $t->chave_origem_destino,
                $t->km_trecho !== null ? number_format($t->km_trecho, 1, ',', '') : '',
                $t->prazo_horas_normal,
                $t->prazo_horas_expresso,
                $t->prazo_padrao?->label(),
            ];
        }

        $csv = collect($linhas)
            ->map(fn (array $l) => collect($l)->map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"')->implode(';'))
            ->implode("\n");

        return response("\u{FEFF}".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="trechos-sap-'.now()->format('Y-m-d_H-i').'.csv"',
        ]);
    }
}
