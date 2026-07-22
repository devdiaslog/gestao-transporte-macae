<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Observação acumulativa do item: SAP, operador e API sempre acrescentam
     * (texto anterior + linha em branco + novo), nunca sobrescrevem.
     */
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->text('observacao')->nullable()->after('data_hora_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropColumn('observacao');
        });
    }
};
