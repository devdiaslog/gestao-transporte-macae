<?php

namespace App\Http\Controllers;

use App\Services\BigcoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BigcoreController extends Controller
{
    public function veiculo(Request $request, BigcoreService $bigcore): JsonResponse
    {
        $placa = strtoupper(trim($request->string('placa')));

        if (! $placa) {
            return response()->json(['erro' => 'Placa não informada.'], 422);
        }

        $dados = $bigcore->buscarPorPlaca($placa);

        if (! $dados) {
            return response()->json(['erro' => 'Veículo não encontrado no Elog.'], 404);
        }

        return response()->json($dados);
    }
}
