<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportarDemandasApiRequest;
use App\Services\ImportadorDemandas;
use Illuminate\Http\JsonResponse;

class DemandaImportacaoController extends Controller
{
    /**
     * Recebe itens de demanda do SAP em JSON e aplica as mesmas regras da
     * importação por planilha: demanda localizada/criada pela nota, campos
     * mestres sincronizados, campos do operador preservados, remanejo de RT
     * e recálculo dos campos derivados.
     *
     * Payload: {"itens": [{"nota": "509538496", "numero_rt": "326741968",
     * "numero_item": "1", "subitem": "5", "local_origem": "PACU", ...}]}
     */
    public function store(ImportarDemandasApiRequest $request, ImportadorDemandas $importador): JsonResponse
    {
        $resultado = $importador->importarLinhas(
            $request->validated('itens'),
            $request->user()->id,
        );

        return response()->json([
            'ok' => $resultado['erros'] === [],
            ...$resultado,
        ]);
    }
}
