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

    /**
     * Mapa curado: status conhecido → token de cor fixo.
     * Novos status não listados recebem cor via hash CRC32.
     *
     * @var array<string, string>
     */
    private const STATUS_COLOR_MAP = [
        'Ag-Carregamento' => 'amber',
        'Ag-Descarregamento' => 'orange',
        'Ag-Motorista' => 'yellow',
        'Carregado' => 'emerald',
        'Carregando' => 'teal',
        'Descarregando' => 'violet',
        'Descarregado' => 'purple',
        'Em Trânsito' => 'blue',
        'Em Viagem' => 'indigo',
        'Disponível' => 'lime',
        'Manutenção' => 'rose',
        'Parado' => 'zinc',
    ];

    /**
     * Paleta de cores para status desconhecidos (fallback por hash CRC32).
     *
     * @var array<int, string>
     */
    private const STATUS_COLOR_PALETTE = [
        'amber', 'orange', 'yellow', 'lime', 'emerald',
        'teal', 'cyan', 'blue', 'indigo', 'violet', 'purple', 'rose',
    ];

    public function index(): View
    {
        $vehicles = $this->fetchVehicles();
        $divisions = $this->extractDivisions($vehicles);
        $statuses = $this->extractStatuses($vehicles);
        $statusColorMap = $this->buildStatusColorMap($statuses);

        return view('control-tower.index', compact('vehicles', 'divisions', 'statuses', 'statusColorMap'));
    }

    /**
     * Endpoint JSON para o botão "Atualizar Agora" (via fetch JS).
     * Sempre invalida o cache e busca dados frescos da API.
     */
    public function dados(): JsonResponse
    {
        Cache::forget(self::CACHE_KEY);

        $vehicles = $this->fetchVehicles();
        $divisions = $this->extractDivisions($vehicles);
        $statuses = $this->extractStatuses($vehicles);
        $statusColorMap = $this->buildStatusColorMap($statuses);

        return response()->json([
            'success' => true,
            'vehicles' => $vehicles,
            'total' => count($vehicles),
            'divisions' => $divisions,
            'statuses' => $statuses,
            'statusColorMap' => $statusColorMap,
        ]);
    }

    /**
     * Extrai valores únicos do campo Divisão, ordenados alfabeticamente.
     * Registros com Divisão nula/vazia são representados por string vazia
     * e exibidos como "Sem divisão" na UI.
     *
     * @param  array<int, array<string, mixed>>  $vehicles
     * @return array<int, string>
     */
    private function extractDivisions(array $vehicles): array
    {
        $map = [];
        $hasSemDivisao = false;

        foreach ($vehicles as $v) {
            $div = $v['Divisão'] ?? null;

            if ($div === null || $div === '') {
                $hasSemDivisao = true;
            } else {
                $map[(string) $div] = true;
            }
        }

        $result = array_keys($map);
        sort($result);

        if ($hasSemDivisao) {
            $result[] = ''; // '' = "Sem divisão" na UI
        }

        return $result;
    }

    /**
     * Extrai valores únicos do campo Status (operacional), ordenados por prioridade visual.
     * Null/vazio é representado por string vazia e exibido como "Indefinido" na UI.
     *
     * @param  array<int, array<string, mixed>>  $vehicles
     * @return array<int, string>
     */
    private function extractStatuses(array $vehicles): array
    {
        $map = [];
        $hasIndefinido = false;

        foreach ($vehicles as $v) {
            $status = $v['Status'] ?? null;

            if ($status === null || $status === '') {
                $hasIndefinido = true;
            } else {
                $map[(string) $status] = true;
            }
        }

        $result = array_keys($map);

        usort($result, function (string $a, string $b): int {
            $pa = $this->statusPriority($a);
            $pb = $this->statusPriority($b);

            return $pa !== $pb ? $pa <=> $pb : strcasecmp($a, $b);
        });

        if ($hasIndefinido) {
            $result[] = ''; // '' = "Indefinido" na UI
        }

        return $result;
    }

    /**
     * Busca veículos da API TMS, com cache de 5 minutos.
     *
     * A API Bigcore retorna o formato {headers: [...], rows: [...], styles: [...]}.
     * O mapeamento é feito por POSIÇÃO (não por array_combine) porque a API retorna
     * dois headers com o mesmo nome "Status": o índice 18 é o status operacional
     * (Ag-Carregamento, Ag-Motorista, Carregado, etc.) e o índice 36 é o status
     * do rastreador. O array_combine manteria apenas o último, perdendo o status operacional.
     *
     * Índices extraídos (verificados contra headers reais da API):
     *   [2]  Condutor                [3]  Placa
     *   [4]  CM                      [5]  Sr
     *   [6]  Sr Placa                [7]  Operação
     *   [8]  Divisão                 [10] Rota
     *   [11] Documento               [12] Macro
     *   [13] Macro Data              [14] Latitude
     *   [15] Longitude               [16] Local
     *   [17] Endereço                [18] Status ← OPERACIONAL
     *   [19] Obs. Vix                [20] Obs. Petrobras
     *   [21] Cerca 1                 [22] Cerca 1 Data Entrada
     *   [23] Cerca 1 Tempo Máximo    [24] Cerca 1 Atividade
     *   [25] Cerca 2                 [26] Cerca 2 Data Entrada
     *   [27] Cerca 2 Tempo Máximo    [28] Cerca 2 Atividade
     *   [29] Cerca 3                 [30] Cerca 3 Data Entrada
     *   [31] Cerca 3 Tempo Máximo    [32] Cerca 3 Atividade
     *   [33] Início Jornada Operacional
     *   [34] Disponibilidade         [35] Disponibilidade Data
     *   [36] Status ← RASTREADOR     [37] Status Data
     *   [38] Conexão                 [39] Sinal
     *   [40] Comunicação             [41] Motor ← "Ligado"/"Desligado"
     *   [42] Velocidade (km/h)       [43] Litrômetro
     *   [44] % Tanque                [45] Odômetro (metros)
     *   [46] Rastreador (marca)
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
     * Apenas os índices relevantes são extraídos — ignorados: 0,1,9,29-36,47,48,50.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        $col = fn (int $i): mixed => $row[$i] ?? null;

        return [
            'Condutor' => $col(2),
            'Placa' => $col(3),
            'CM' => $col(4),
            'SR' => $col(5),
            'SR Placa' => $col(6),
            'Operação' => $col(7),
            'Divisão' => $col(8),
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
            'Início Jornada' => $col(33),
            'Disponibilidade' => $col(34),
            'Disponibilidade Data' => $col(35),
            'Status Rastreador' => $col(36),
            'Status Data' => $col(37),
            'Conexão' => $col(38),
            'Sinal' => $col(39),
            'Comunicação' => $col(40),
            'Motor' => $col(41), // "Ligado" / "Desligado"
            'Velocidade' => $col(42), // km/h
            'Litrômetro' => $col(43),
            'Odômetro' => $col(45), // metros
            'Rastreador' => $col(46), // marca do rastreador
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

    /**
     * Retorna o token de cor fixo para um status operacional.
     * Usa o mapa curado para status conhecidos; para os demais aplica
     * hash CRC32 sobre a string para garantir cor estável e determinística.
     */
    private function statusColorToken(string $status): string
    {
        if ($status === '') {
            return 'zinc';
        }

        if (isset(self::STATUS_COLOR_MAP[$status])) {
            return self::STATUS_COLOR_MAP[$status];
        }

        $index = abs(crc32($status)) % count(self::STATUS_COLOR_PALETTE);

        return self::STATUS_COLOR_PALETTE[$index];
    }

    /**
     * Constrói o mapa status → token de cor para os statuses presentes nos dados.
     *
     * @param  array<int, string>  $statuses
     * @return array<string, string>
     */
    private function buildStatusColorMap(array $statuses): array
    {
        $map = [];

        foreach ($statuses as $s) {
            $map[$s] = $this->statusColorToken($s);
        }

        return $map;
    }
}
