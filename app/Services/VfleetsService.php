<?php

namespace App\Services;

use App\Models\Equipamento;
use App\Models\PosicaoVeiculo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VfleetsService
{
    private const TOKEN_CACHE_KEY = 'vfleets_access_token';

    private string $tokenUrl;

    private string $apiUrl;

    private string $clientId;

    private string $clientSecret;

    private string $grantType;

    public function __construct()
    {
        $this->tokenUrl = config('services.vfleets.token_url');
        $this->apiUrl = config('services.vfleets.api_url');
        $this->clientId = config('services.vfleets.client_id');
        $this->clientSecret = config('services.vfleets.client_secret');
        $this->grantType = config('services.vfleets.grant_type');
    }

    /**
     * Obtém e cacheia o token Bearer por 25 min (expira em 30 na API).
     *
     * @throws RuntimeException
     */
    private function token(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(25), function () {
            $response = Http::asForm()->post($this->tokenUrl, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => $this->grantType,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Vfleets: falha ao obter token — '.$response->status());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Determina o estado do rastreador com base na velocidade.
     */
    private function deriveTrackerState(?float $speed): string
    {
        if ($speed === null) {
            return 'Sem Sinal';
        }

        return $speed > 0 ? 'Em Movimento' : 'Parado';
    }

    /**
     * Retorna os campos de estado calculados para um registro.
     * - Estado igual ao anterior: mantém state_since existente (timer continua).
     * - Estado diferente ou primeiro sync: usa position_at como melhor aproximação
     *   de quando o veículo entrou no estado atual; cai em $now só se não houver.
     */
    private function resolveStateFields(?float $speed, ?PosicaoVeiculo $existing, Carbon $now, ?Carbon $positionAt): array
    {
        $newState = $this->deriveTrackerState($speed);

        if ($existing && $existing->tracker_state === $newState) {
            $stateSince = $existing->state_since;
        } else {
            $stateSince = $positionAt ?? $now;
        }

        return ['tracker_state' => $newState, 'state_since' => $stateSince];
    }

    /**
     * Converte um timestamp UTC enviado pela API para o timezone da aplicação.
     * A API envia timestamps em UTC sem marcador de fuso; armazenamos em BRT para
     * que o Eloquent (que assume o app.timezone ao ler strings do banco) interprete
     * os valores corretamente em comparações e cálculos de duração.
     */
    private function parseApiDatetime(string $value): Carbon
    {
        return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone'));
    }

    /**
     * Monta o array de atributos comuns para updateOrCreate.
     *
     * @param  array<string, mixed>  $pos
     */
    private function buildAttributes(array $pos, Carbon $syncedAt, array $stateFields): array
    {
        return [
            'prefix' => $pos['prefix'] ?? null,
            'chassis' => $pos['chassis'] ?? null,
            'position_at' => isset($pos['dateTime']) ? $this->parseApiDatetime($pos['dateTime']) : null,
            'latitude' => $pos['latitude'] ?? null,
            'longitude' => $pos['longitude'] ?? null,
            'speed' => $pos['speed'] ?? null,
            'ignition' => $pos['ignition'] ?? null,
            'direction' => $pos['direction'] ?? null,
            'hodometer' => $pos['hodometer'] ?? null,
            'gps_status' => $pos['gpsStatus'] ?? null,
            'gsm_status' => $pos['gsmStatus'] ?? null,
            'event_type' => $pos['eventType'] ?? null,
            'transmission_way' => $pos['transmissionWay'] ?? null,
            'rfid' => $pos['rfid'] ?? null,
            'synced_at' => $syncedAt,
            ...$stateFields,
        ];
    }

    /**
     * Busca a última posição de uma placa específica na API e salva no banco.
     * Retorna o registro atualizado, ou null se a placa não for encontrada.
     *
     * @throws RuntimeException
     */
    public function sincronizarPlaca(string $plate): ?PosicaoVeiculo
    {
        $response = Http::withToken($this->token())
            ->timeout(30)
            ->get("{$this->apiUrl}/last");

        if (! $response->successful()) {
            Log::error('Vfleets: erro ao buscar posições', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Vfleets: erro ao buscar posições — '.$response->status());
        }

        $syncedAt = now();

        foreach ($response->json() ?? [] as $pos) {
            if (($pos['licensePlate'] ?? null) !== $plate) {
                continue;
            }

            $existing = PosicaoVeiculo::where('license_plate', $plate)->first();
            $positionAt = isset($pos['dateTime']) ? $this->parseApiDatetime($pos['dateTime']) : null;
            $stateFields = $this->resolveStateFields($pos['speed'] ?? null, $existing, $syncedAt, $positionAt);

            return PosicaoVeiculo::updateOrCreate(
                ['license_plate' => $plate],
                $this->buildAttributes($pos, $syncedAt, $stateFields),
            );
        }

        return null;
    }

    /**
     * Segundos desde a última sincronização bem-sucedida, ou null se nunca
     * houve nenhuma.
     */
    public function segundosDesdeUltimaSincronizacao(): ?int
    {
        $ultima = PosicaoVeiculo::max('synced_at');

        return $ultima ? (int) Carbon::parse($ultima)->diffInSeconds(now()) : null;
    }

    /**
     * A última sincronização é recente o bastante para a tela reaproveitá-la.
     */
    public function sincronizadoRecentemente(): bool
    {
        $idade = $this->segundosDesdeUltimaSincronizacao();

        return $idade !== null && $idade < (int) config('services.vfleets.intervalo_sincronizacao');
    }

    /**
     * Busca as últimas posições na API e salva no banco via upsert.
     * Retorna o total de registros sincronizados.
     *
     * @throws RuntimeException
     */
    public function sincronizar(): int
    {
        $response = Http::withToken($this->token())
            ->timeout(30)
            ->get("{$this->apiUrl}/last");

        if (! $response->successful()) {
            Log::error('Vfleets: erro ao buscar posições', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Vfleets: erro ao buscar posições — '.$response->status());
        }

        $posicoes = $response->json() ?? [];
        $syncedAt = now();
        $total = 0;

        // Pré-carrega registros existentes para evitar N+1 nas comparações de estado
        $plates = collect($posicoes)->pluck('licensePlate')->filter()->unique()->values()->all();
        $existing = PosicaoVeiculo::whereIn('license_plate', $plates)
            ->get()
            ->keyBy('license_plate');

        // Pré-carrega equipamentos pelo id_rastreador (license_plate) para geofencing
        $equipamentos = Equipamento::query()
            ->with('posicao')
            ->where('status', true)
            ->whereIn('id_rastreador', $plates)
            ->get()
            ->keyBy('id_rastreador');

        $geofencing = app(GeofencingService::class);

        foreach ($posicoes as $pos) {
            $plate = $pos['licensePlate'] ?? null;

            if (! $plate) {
                continue;
            }

            $positionAt = isset($pos['dateTime']) ? $this->parseApiDatetime($pos['dateTime']) : null;
            $stateFields = $this->resolveStateFields($pos['speed'] ?? null, $existing->get($plate), $syncedAt, $positionAt);

            $posicaoAtualizada = PosicaoVeiculo::updateOrCreate(
                ['license_plate' => $plate],
                $this->buildAttributes($pos, $syncedAt, $stateFields),
            );

            // Geofencing: detecta entrada/saída em cercas
            $equipamento = $equipamentos->get($plate);
            if ($equipamento) {
                $equipamento->setRelation('posicao', $posicaoAtualizada);
                $geofencing->processarVeiculo($equipamento, $syncedAt);
            }

            $total++;
        }

        return $total;
    }
}
