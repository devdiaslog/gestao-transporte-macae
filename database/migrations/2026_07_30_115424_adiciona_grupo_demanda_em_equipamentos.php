<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grupo efetivo do veículo (sinergia): tipo (load/backload/transferencia)
     * da última demanda em andamento iniciada nele. Nulo → cai na subdivisão.
     */
    public function up(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->string('grupo_demanda', 20)->nullable()->after('destino');
        });
    }

    public function down(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->dropColumn('grupo_demanda');
        });
    }
};
