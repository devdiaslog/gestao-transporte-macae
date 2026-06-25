<?php

namespace App\Services;

use App\Models\Equipamento;
use App\Models\StatusEvento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StatusOperacionalService
{
    /**
     * Processa um lote de registros da API Bigcore, atualizando os eventos de status.
     *
     * @param  array<int, array<string, mixed>>  $registros
     */
    public function sincronizar(array $registros, Carbon $agora): int
    {
        if (empty($registros)) {
            return 0;
        }

        // Pré-carrega equipamentos pela placa para evitar N+1
        $placas = collect($registros)
            ->pluck('licensePlate')
            ->filter()
            ->map(fn ($p) => strtoupper($p))
            ->unique()
            ->values()
            ->all();

        /** @var Collection<string, Equipamento> $equipamentos */
        $equipamentos = Equipamento::whereIn('placa', $placas)
            ->get()
            ->keyBy(fn ($e) => strtoupper($e->placa));

        // Pré-carrega eventos abertos para esses equipamentos
        $equipamentoIds = $equipamentos->pluck('id')->values()->all();

        /** @var Collection<int, StatusEvento> $eventosAbertos */
        $eventosAbertos = StatusEvento::whereNull('saida_em')
            ->whereIn('equipamento_id', $equipamentoIds)
            ->get()
            ->keyBy('equipamento_id');

        $total = 0;

        foreach ($registros as $registro) {
            $placa = strtoupper($registro['licensePlate'] ?? '');

            if (! $placa) {
                continue;
            }

            $equipamento = $equipamentos->get($placa);

            if (! $equipamento || $equipamento->status_manual) {
                continue;
            }

            $novoStatus = $registro['observationOne'] ?? null;
            $novoDocumento = $registro['document']['documentCode'] ?? null;
            $novaObservacao = $registro['observationTwo'] ?? null;

            // Sem status operacional → não há o que registrar
            if (! $novoStatus) {
                continue;
            }

            $eventoAberto = $eventosAbertos->get($equipamento->id);

            if (! $eventoAberto) {
                // Nenhum evento aberto → abre um novo
                $novo = StatusEvento::create([
                    'equipamento_id' => $equipamento->id,
                    'status_operacional' => $novoStatus,
                    'documento' => $novoDocumento ?: null,
                    'observacao' => $novaObservacao ?: null,
                    'entrada_em' => $agora,
                ]);

                $eventosAbertos->put($equipamento->id, $novo);
                $total++;

                continue;
            }

            $mesmoStatus = $eventoAberto->status_operacional === $novoStatus;
            $mesmoDocumento = ($eventoAberto->documento ?: null) === ($novoDocumento ?: null);

            if ($mesmoStatus && $mesmoDocumento) {
                // Mesmo estado — apenas atualiza observação se mudou
                if (($eventoAberto->observacao ?: null) !== ($novaObservacao ?: null)) {
                    $eventoAberto->update(['observacao' => $novaObservacao ?: null]);
                }

                continue;
            }

            // Mudou status ou documento → fecha o atual e abre um novo
            $eventoAberto->update([
                'saida_em' => $agora,
                'duracao_minutos' => (int) $eventoAberto->entrada_em->diffInMinutes($agora),
            ]);

            $novo = StatusEvento::create([
                'equipamento_id' => $equipamento->id,
                'status_operacional' => $novoStatus,
                'documento' => $novoDocumento ?: null,
                'observacao' => $novaObservacao ?: null,
                'entrada_em' => $agora,
            ]);

            $eventosAbertos->put($equipamento->id, $novo);
            $total++;
        }

        return $total;
    }
}
