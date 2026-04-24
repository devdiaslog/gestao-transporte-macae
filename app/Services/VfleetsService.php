<?php

namespace App\Services;

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

            return PosicaoVeiculo::updateOrCreate(
                ['license_plate' => $plate],
                [
                    'prefix' => $pos['prefix'] ?? null,
                    'chassis' => $pos['chassis'] ?? null,
                    'position_at' => isset($pos['dateTime']) ? Carbon::parse($pos['dateTime']) : null,
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
                ]
            );
        }

        return null;
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

        foreach ($posicoes as $pos) {
            $plate = $pos['licensePlate'] ?? null;

            if (! $plate) {
                continue;
            }

            PosicaoVeiculo::updateOrCreate(
                ['license_plate' => $plate],
                [
                    'prefix' => $pos['prefix'] ?? null,
                    'chassis' => $pos['chassis'] ?? null,
                    'position_at' => isset($pos['dateTime']) ? Carbon::parse($pos['dateTime']) : null,
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
                ]
            );

            $total++;
        }

        return $total;
    }
}
