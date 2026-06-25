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
        Schema::dropIfExists('etapas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipamento_id')->constrained('equipamentos')->cascadeOnDelete();
            $table->foreignId('tipo_etapa_id')->nullable()->constrained('tipo_etapas')->nullOnDelete();
            $table->foreignId('cerca_id')->nullable()->constrained('cercas')->nullOnDelete();
            $table->foreignId('motorista_id')->nullable()->constrained('motoristas')->nullOnDelete();
            $table->string('documento', 100)->nullable();
            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim')->nullable();
            $table->text('observacao')->nullable();
            $table->string('motivo_longa_duracao', 500)->nullable();
            $table->foreignId('emitido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finalizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('auditado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('auditado_em')->nullable();
            $table->timestamps();
        });
    }
};
