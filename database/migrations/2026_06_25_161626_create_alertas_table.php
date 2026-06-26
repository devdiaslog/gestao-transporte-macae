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
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipamento_id')->constrained('equipamentos')->cascadeOnDelete();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lembrete', 500);
            $table->string('tipo', 30)->default('tempo'); // tempo | rastreador | elog (futuro)
            $table->dateTime('data_hora_alerta')->nullable();
            $table->string('condicao', 100)->nullable(); // gatilho de rastreador/elog (futuro)
            $table->boolean('para_todos')->default(false);
            $table->string('status', 20)->default('pendente'); // pendente | resolvido
            $table->foreignId('resolvido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolvido_em')->nullable();
            $table->timestamps();

            $table->index(['status', 'data_hora_alerta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
