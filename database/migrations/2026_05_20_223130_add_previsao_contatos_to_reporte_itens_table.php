<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporte_itens', function (Blueprint $table) {
            $table->string('data_hora_previsao', 30)->nullable()->after('tempo_parado');
            $table->string('primeiro_contato', 100)->nullable()->after('data_hora_previsao');
            $table->string('segundo_contato', 100)->nullable()->after('primeiro_contato');
        });
    }

    public function down(): void
    {
        Schema::table('reporte_itens', function (Blueprint $table) {
            $table->dropColumn(['data_hora_previsao', 'primeiro_contato', 'segundo_contato']);
        });
    }
};
