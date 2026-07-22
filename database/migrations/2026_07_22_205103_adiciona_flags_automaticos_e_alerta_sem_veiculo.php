<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flags de início/fim definidos automaticamente pelo SAP (hora genérica
     * 00:00): sinalizam ao operador quais demandas precisam de ajuste; ao
     * ajustar, o horário passa a ser do operador. Alertas de demanda podem
     * existir sem veículo vinculado.
     */
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->boolean('inicio_automatico')->default(false)->after('data_hora_inicio_demanda');
            $table->boolean('fim_automatico')->default(false)->after('data_hora_fim_demanda');
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->foreignId('equipamento_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn(['inicio_automatico', 'fim_automatico']);
        });

        Schema::table('alertas', function (Blueprint $table) {
            $table->foreignId('equipamento_id')->nullable(false)->change();
        });
    }
};
