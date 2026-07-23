<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peso total e dimensões da carga (RT) vindos do SAP — sempre
     * re-sincronizados na importação; habilitam análises de ocupação.
     */
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->decimal('peso_total', 12, 3)->nullable()->after('descricao_item');
            $table->decimal('altura', 8, 3)->nullable()->after('peso_total');
            $table->decimal('largura', 8, 3)->nullable()->after('altura');
            $table->decimal('comprimento', 8, 3)->nullable()->after('largura');
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropColumn(['peso_total', 'altura', 'largura', 'comprimento']);
        });
    }
};
