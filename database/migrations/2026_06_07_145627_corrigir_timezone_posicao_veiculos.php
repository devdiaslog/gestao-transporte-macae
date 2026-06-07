<?php

use App\Models\PosicaoVeiculo;
use Illuminate\Database\Migrations\Migration;

/**
 * Corrige o timezone de position_at e state_since na tabela posicao_veiculos.
 *
 * Problema: a API envia timestamps em UTC, mas o VfleetsService os armazenava
 * sem converter para o timezone da aplicação (America/Sao_Paulo, UTC-3).
 * O Eloquent lê strings do banco assumindo o app.timezone, então valores UTC
 * eram interpretados como BRT — 3 horas a mais do que o correto.
 *
 * Fix: subtrai 3 horas dos registros existentes para que, após a correção no
 * VfleetsService (que agora converte para BRT antes de gravar), os dados
 * antigos fiquem alinhados com os novos.
 *
 * Observação: synced_at é gerado por now() do Laravel (já em BRT) e está
 * correto — não deve ser alterado.
 */
return new class extends Migration
{
    public function up(): void
    {
        PosicaoVeiculo::query()
            ->whereNotNull('position_at')
            ->get()
            ->each(function (PosicaoVeiculo $posicao) {
                $posicao->timestamps = false;
                $posicao->update([
                    'position_at' => $posicao->position_at->subHours(3),
                    'state_since' => $posicao->state_since?->subHours(3),
                ]);
            });
    }

    public function down(): void
    {
        PosicaoVeiculo::query()
            ->whereNotNull('position_at')
            ->get()
            ->each(function (PosicaoVeiculo $posicao) {
                $posicao->timestamps = false;
                $posicao->update([
                    'position_at' => $posicao->position_at->addHours(3),
                    'state_since' => $posicao->state_since?->addHours(3),
                ]);
            });
    }
};
