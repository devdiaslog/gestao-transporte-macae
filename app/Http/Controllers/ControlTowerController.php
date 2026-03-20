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
     * O mapeamento é feito por POSIÇÃO (não por array_combine) porque a API retorna
     * dois headers com o mesmo nome "Status": o índice 18 é o status operacional
     * (Ag-Carregamento, Ag-Motorista, Carregado, etc.) e o índice 40 é o status
     * do rastreador (parado/em movimento). O array_combine manteria apenas o último,
     * perdendo o status operacional.
     *
     * Mapeamento completo (índice → chave):
     *   [0]  Tipo Veículo            [1]  Terceirizado
     *   [2]  Condutor                [3]  Placa
     *   [4]  CM                      [5]  SR
     *   [6]  SR Placa                [7]  Operação
     *   [8]  Divisão                 [9]  CTE
     *   [10] Rota                    [11] Documento
     *   [12] Macro                   [13] Macro Data
     *   [14] Latitude                [15] Longitude
     *   [16] Local                   [17] Endereço
     *   [18] Status ← OPERACIONAL    [19] Obs. Vix
     *   [20] Obs. Petrobras          [21] Cerca 1
     *   [22] Cerca 1 Entrada         [23] Cerca 1 Tempo Máx.
     *   [24] Cerca 1 Atividade       [25] Cerca 2
     *   [26] Cerca 2 Entrada         [27] Cerca 2 Tempo Máx.
     *   [28] Cerca 2 Atividade       [29] Cerca 3
     *   [30] Cerca 3 Entrada         [31] Cerca 3 Tempo Máx.
     *   [32] Cerca 3 Atividade       [33] Cerca 4
     *   [34] Cerca 4 Entrada         [35] Cerca 4 Tempo Máx.
     *   [36] Cerca 4 Atividade       [37] Início Jornada
     *   [38] Disponibilidade         [39] Disponibilidade Data
     *   [40] Rastreador ← MOVIMENTO  [41] Rastreador Data
     *   [42] Conexão                 [43] Sinal Data
     *   [44] Comunicação             [45] Motor
     *   [46] Velocidade              [47] Litrômetro
     *   [48] Percentual Tanque       [49] Odômetro
     *   [50] Rastreador ID
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

                if (! isset($data['rows'])) {
                    Log::warning('[TMS] Resposta sem rows', ['keys' => is_array($data) ? array_keys($data) : []]);

                    return [];
                }

                $vehicles = array_map(fn (array $row): array => $this->mapRow($row), $data['rows']);

                usort($vehicles, function (array $a, array $b): int {
                    $pa = $this->statusPriority((string) ($a['Status'] ?? ''));
                    $pb = $this->statusPriority((string) ($b['Status'] ?? ''));

                    return $pa !== $pb
                        ? $pa <=> $pb
                        : strcasecmp((string) ($a['Status'] ?? ''), (string) ($b['Status'] ?? ''));
                });

                Log::info('[TMS] Veículos montados', ['total' => count($vehicles)]);

                return $vehicles;

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

    /**
     * Converte um row posicional da API Bigcore em array associativo com chaves únicas.
     * Resolve o conflito de dois headers "Status" usando nomes distintos.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        $col = fn (int $i): mixed => $row[$i] ?? null;

        return [
            'Tipo Veículo' => $col(0),
            'Terceirizado' => $col(1),
            'Condutor' => $col(2),
            'Placa' => $col(3),
            'CM' => $col(4),
            'SR' => $col(5),
            'SR Placa' => $col(6),
            'Operação' => $col(7),
            'Divisão' => $col(8),
            'CTE' => $col(9),
            'Rota' => $col(10),
            'Documento' => $col(11),
            'Macro' => $col(12),
            'Macro Data' => $col(13),
            'Latitude' => $col(14),
            'Longitude' => $col(15),
            'Local' => $col(16),
            'Endereço' => $col(17),
            'Status' => $col(18), // Status Operacional (Ag-*, Carregado, etc.)
            'Obs. Vix' => $col(19),
            'Obs. Petrobras' => $col(20),
            'Cerca 1' => $col(21),
            'Cerca 1 Entrada' => $col(22),
            'Cerca 1 Tempo Máx.' => $col(23),
            'Cerca 1 Atividade' => $col(24),
            'Cerca 2' => $col(25),
            'Cerca 2 Entrada' => $col(26),
            'Cerca 2 Tempo Máx.' => $col(27),
            'Cerca 2 Atividade' => $col(28),
            'Cerca 3' => $col(29),
            'Cerca 3 Entrada' => $col(30),
            'Cerca 3 Tempo Máx.' => $col(31),
            'Cerca 3 Atividade' => $col(32),
            'Cerca 4' => $col(33),
            'Cerca 4 Entrada' => $col(34),
            'Cerca 4 Tempo Máx.' => $col(35),
            'Cerca 4 Atividade' => $col(36),
            'Início Jornada' => $col(37),
            'Disponibilidade' => $col(38),
            'Disponibilidade Data' => $col(39),
            'Rastreador' => $col(40), // Status de movimento (parado/em movimento)
            'Rastreador Data' => $col(41),
            'Conexão' => $col(42),
            'Sinal Data' => $col(43),
            'Comunicação' => $col(44),
            'Motor' => $col(45),
            'Velocidade' => $col(46),
            'Litrômetro' => $col(47),
            'Percentual Tanque' => $col(48),
            'Odômetro' => $col(49),
            'Rastreador ID' => $col(50),
        ];
    }

    /**
     * Retorna a prioridade de ordenação para um status operacional.
     * Valores menores aparecem primeiro no grid.
     *
     * 1 — Ag-*         (aguardando — âmbar)
     * 2 — Carregado    (carregado  — emerald)
     * 3 — Trânsito     (em viagem  — azul)
     * 4 — Descarreg.   (descarreg. — violeta)
     * 5 — Outros       (zinc)
     */
    private function statusPriority(string $status): int
    {
        return match (true) {
            str_starts_with($status, 'Ag-') => 1,
            (bool) preg_match('/carregado|carregando/i', $status) => 2,
            (bool) preg_match('/trans[ií]t|viagem|em tr/i', $status) => 3,
            (bool) preg_match('/descarreg/i', $status) => 4,
            default => 5,
        };
    }
}
