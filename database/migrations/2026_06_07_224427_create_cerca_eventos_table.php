<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cerca_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cerca_id')->constrained('cercas')->cascadeOnDelete();
            $table->foreignId('equipamento_id')->constrained('equipamentos')->cascadeOnDelete();
            $table->datetime('entrada_em');
            $table->datetime('saida_em')->nullable();
            $table->unsignedSmallInteger('duracao_minutos')->nullable();
            $table->boolean('excedeu_maximo')->default(false);
            $table->timestamps();

            $table->index(['equipamento_id', 'saida_em']);
            $table->index(['cerca_id', 'saida_em']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cerca_eventos');
    }
};
