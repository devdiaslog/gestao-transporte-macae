<?php

namespace App\Services;

use App\Models\Cerca;
use App\Models\CercaEvento;
use App\Models\Equipamento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GeofencingService
{
    /**
     * Ray casting — verifica se um ponto [lat, lng] está dentro de um polígono.
     *
     * @param  array<int, array{0: float, 1: float}>  $poligono
     */
    public function pontoDentro(float $lat, float $lng, array $poligono): bool
    {
        $dentro = false;
        $n = count($poligono);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $poligono[$i][0];
            $yi = $poligono[$i][1];
            $xj = $poligono[$j][0];
            $yj = $poligono[$j][1];

            if ((($yi > $lng) !== ($yj > $lng)) && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $dentro = ! $dentro;
            }
        }

        return $dentro;
    }

    /**
     * Processa eventos de geofencing para um veículo após sync de posição.
     * - Entrada: abre novo evento
     * - Saída com duração < tempo_minimo: descarta (manobra, trânsito, etc.)
     * - Saída com duração >= tempo_minimo: fecha o evento, aplica flag excedeu_maximo
     */
    public function processarVeiculo(Equipamento $equipamento, Carbon $agora): void
    {
        if (! $equipamento->posicao?->latitude || ! $equipamento->posicao?->longitude) {
            return;
        }

        $lat = (float) $equipamento->posicao->latitude;
        $lng = (float) $equipamento->posicao->longitude;

        /** @var Collection<int, Cerca> $cercas */
        $cercas = Cerca::query()
            ->where('status', true)
            ->whereNotNull('poligono')
            ->get();

        foreach ($cercas as $cerca) {
            if (! is_array($cerca->poligono) || count($cerca->poligono) < 3) {
                continue;
            }

            $dentro = $this->pontoDentro($lat, $lng, $cerca->poligono);

            /** @var CercaEvento|null $eventoAberto */
            $eventoAberto = CercaEvento::query()
                ->where('cerca_id', $cerca->id)
                ->where('equipamento_id', $equipamento->id)
                ->whereNull('saida_em')
                ->latest('entrada_em')
                ->first();

            if ($dentro && ! $eventoAberto) {
                // Entrou na cerca → abre evento
                CercaEvento::create([
                    'cerca_id' => $cerca->id,
                    'equipamento_id' => $equipamento->id,
                    'entrada_em' => $agora,
                ]);

                continue;
            }

            if (! $dentro && $eventoAberto) {
                // Saiu da cerca → calcula duração
                $duracaoMinutos = (int) $eventoAberto->entrada_em->diffInMinutes($agora);

                if ($duracaoMinutos < $cerca->tempo_minimo) {
                    // Permanência abaixo do mínimo → descarta (ruído)
                    $eventoAberto->delete();
                } else {
                    // Permanência válida → fecha o evento
                    $eventoAberto->update([
                        'saida_em' => $agora,
                        'duracao_minutos' => $duracaoMinutos,
                        'excedeu_maximo' => $duracaoMinutos > $cerca->tempo_maximo,
                    ]);
                }
            }
        }
    }
}
