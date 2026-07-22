<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos mestres alterados manualmente pelo operador; a importação do SAP
     * deixa de sincronizá-los naquele item.
     */
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->json('campos_editados')->nullable()->after('data_hora_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropColumn('campos_editados');
        });
    }
};
