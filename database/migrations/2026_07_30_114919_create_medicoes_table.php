<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Medições: ciclos/períodos usados para filtrar as demandas no dashboard.
     */
    public function up(): void
    {
        Schema::create('medicoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome_medicao');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->timestamps();

            $table->index('data_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicoes');
    }
};
