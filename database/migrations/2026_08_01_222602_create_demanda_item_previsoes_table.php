<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico das previsões de entrega de um item.
 *
 * Cada promessa feita ao cliente vira uma linha. O item guarda apenas a
 * previsão vigente; é aqui que fica o registro de quantas vezes a data foi
 * remarcada, por quem e por quê.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demanda_item_previsoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demanda_item_id')->constrained('demanda_itens')->cascadeOnDelete();

            $table->dateTime('data_hora_previsao');
            $table->string('origem');

            // Nulo quando a previsão vem de automação, sem usuário por trás.
            $table->foreignId('definido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo')->nullable();

            $table->timestamps();

            $table->index(['demanda_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demanda_item_previsoes');
    }
};
