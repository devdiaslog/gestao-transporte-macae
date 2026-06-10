<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class BigcoreService
{
    /**
     * Retorna todos os veículos da API Bigcore.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscarTodos(): array
    {
        $response = Http::withHeaders([
            'Authorization' => config('services.bigcore.token'),
            'TenantId' => config('services.bigcore.tenant'),
            'SubscriptionId' => config('services.bigcore.subscription'),
        ])->get(config('services.bigcore.endpoint'), [
            'Telemetry' => 'true',
            'Rows' => 500,
        ]);

        if (! $response->successful()) {
            return [];
        }

        return $response->json('data', []);
    }

    public function buscarPorPlaca(string $placa): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => config('services.bigcore.token'),
            'TenantId' => config('services.bigcore.tenant'),
            'SubscriptionId' => config('services.bigcore.subscription'),
        ])->get(config('services.bigcore.endpoint'), [
            'Telemetry' => 'true',
            'Rows' => 500,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $veiculo = collect($response->json('data', []))
            ->first(fn ($v) => strtoupper($v['licensePlate'] ?? '') === strtoupper($placa));

        if (! $veiculo) {
            return null;
        }

        return [
            'tempo_parado' => $this->calcTempoParado($veiculo['state']['stateStart'] ?? null),
            'status_operacional' => $veiculo['observationOne'] ?? null,
            'documento' => $veiculo['document']['documentCode'] ?? null,
            'observacao' => $veiculo['observationTwo'] ?? null,
        ];
    }

    private function calcTempoParado(?string $stateStart): ?string
    {
        if (! $stateStart) {
            return null;
        }

        $totalHoras = (int) Carbon::parse($stateStart)->diffInHours(now());
        $dias = intdiv($totalHoras, 24);
        $horas = $totalHoras % 24;

        return "{$dias}d {$horas}h";
    }
}
