<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ControlTowerController extends Controller
{
    private const CACHE_KEY = 'tms_vehicles';

    private const CACHE_TTL = 300; // 5 minutos

    public function index(): View
    {
        $vehicles = $this->fetchVehicles();

        return view('control-tower.index', compact('vehicles'));
    }

    /**
     * Endpoint JSON para o botão "Atualizar Agora" (via fetch JS).
     * Sempre invalida o cache e busca dados frescos da API.
     */
    public function dados(): JsonResponse
    {
        Cache::forget(self::CACHE_KEY);

        $vehicles = $this->fetchVehicles();

        return response()->json([
            'success' => true,
            'vehicles' => $vehicles,
            'total' => count($vehicles),
        ]);
    }

    /**
     * Busca veículos da API TMS, com cache de 5 minutos.
     *
     * A API Bigcore retorna o formato {headers: [...], rows: [...], styles: [...]}.
     * Cada row é convertida em array associativo via array_combine($headers, $row).
     *
     * Índices confirmados da API (posição no array headers/rows):
     *   [3]  Placa           — identificador do veículo
     *   [4]  CM              — identificador da viagem (destaque principal nos cards)
     *   [16] Local           — localização alternativa (fallback quando Cerca 1 está vazia)
     *   [18] Status          — status operacional (Ag-Carregamento, Ag-Motorista, Carregado, etc.)
     *   [21] Cerca 1         — cerca geográfica atual (exibida preferencialmente sobre Local)
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchVehicles(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $endpoint = config('tms.report_endpoint');

            Log::info('[TMS] Iniciando requisição', ['endpoint' => $endpoint]);

            try {
                $response = Http::timeout(config('tms.timeout'))
                    ->withHeaders([
                        'Authorization' => config('tms.token'),
                        'Accept' => 'application/json',
                        'SubscriptionId' => config('tms.subscription_id'),
                        'TenantId' => config('tms.tenant_id'),
                    ])
                    ->get($endpoint);

                Log::info('[TMS] Resposta recebida', [
                    'status' => $response->status(),
                    'ok' => $response->successful(),
                    'preview' => substr($response->body(), 0, 500),
                ]);

                if (! $response->successful()) {
                    Log::warning('[TMS] Resposta não-OK', ['status' => $response->status(), 'body' => $response->body()]);

                    return [];
                }

                $data = $response->json();

                Log::info('[TMS] JSON decodificado', [
                    'type' => gettype($data),
                    'count' => is_array($data) ? count($data) : 'n/a',
                    'keys' => is_array($data) && ! array_is_list($data) ? array_keys($data) : [],
                ]);

                // Formato {headers: [...], rows: [...], styles: [...]}
                if (isset($data['headers'], $data['rows'])) {
                    $headers = $data['headers'];

                    $vehicles = array_map(
                        fn (array $row): array => array_combine($headers, $row),
                        $data['rows']
                    );

                    Log::info('[TMS] Veículos montados', ['total' => count($vehicles)]);

                    return $vehicles;
                }

                // Suporta resposta direta (lista de objetos)
                if (is_array($data) && array_is_list($data)) {
                    return $data;
                }

                return $data['data'] ?? [];

            } catch (\Throwable $e) {
                Log::error('[TMS] Exceção ao chamar API', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return [];
            }
        });
    }
}
