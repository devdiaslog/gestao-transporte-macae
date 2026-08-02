<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportarItensLiberadosApiRequest;
use App\Services\ImportadorItensLiberados;
use Illuminate\Http\JsonResponse;

class ItemLiberadoImportacaoController extends Controller
{
    /**
     * Recebe em JSON os itens que o cliente liberou no SAP (status 03).
     *
     * Os itens entram sem demanda — o atendimento ainda não existe nesse
     * estágio — e são identificados por numero_rt + numero_item + subitem.
     * Reenvio do mesmo item atualiza em vez de duplicar, preservando os campos
     * que o operador já assumiu pela interface.
     *
     * Payload: {"itens": [{"numero_rt": "326213060", "numero_item": "5",
     * "subitem": "2", "prazo_data": "10.07.2026", "prazo_hora": "00:00:00",
     * ...}], "marcar_ausentes": true}
     */
    public function store(ImportarItensLiberadosApiRequest $request, ImportadorItensLiberados $importador): JsonResponse
    {
        $resultado = $importador->importarLinhas(
            $request->validated('itens'),
            $request->user()->id,
            $request->boolean('marcar_ausentes'),
        );

        return response()->json([
            'ok' => $resultado['erros'] === [],
            ...$resultado,
        ]);
    }
}
