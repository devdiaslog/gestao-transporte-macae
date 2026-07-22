<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            // Quando true, o tipo foi fixado manualmente e o cálculo pelos itens não o altera.
            $table->boolean('tipo_demanda_manual')->default(false)->after('tipo_demanda');
        });
    }

    public function down(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn('tipo_demanda_manual');
        });
    }
};
