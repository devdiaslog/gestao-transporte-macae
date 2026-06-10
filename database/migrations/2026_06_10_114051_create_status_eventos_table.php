<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipamento_id')->constrained('equipamentos')->cascadeOnDelete();
            $table->string('status_operacional');
            $table->string('documento')->nullable();
            $table->datetime('entrada_em');
            $table->datetime('saida_em')->nullable();
            $table->unsignedSmallInteger('duracao_minutos')->nullable();
            $table->timestamps();

            $table->index(['equipamento_id', 'saida_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_eventos');
    }
};
