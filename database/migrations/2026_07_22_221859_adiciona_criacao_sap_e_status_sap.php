<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dados geridos pelo SAP: data/hora em que a demanda foi criada lá e o
     * código bruto do status do item (ex.: 04, 07), sempre re-sincronizados
     * na importação — o operador enxerga o estado real do SAP mesmo depois
     * de assumir o status do item no sistema.
     */
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dateTime('data_hora_criacao_sap')->nullable()->after('fonte_demanda');
        });

        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->string('status_sap', 5)->nullable()->after('status_item');
        });
    }

    public function down(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn('data_hora_criacao_sap');
        });

        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropColumn('status_sap');
        });
    }
};
