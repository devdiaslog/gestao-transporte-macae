<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('numero_demanda')->unique();
            $table->string('tipo_cadastro')->default('manual');
            $table->string('tipo_demanda')->nullable();
            $table->foreignId('equipamento_id')->nullable()->constrained('equipamentos')->nullOnDelete();
            $table->foreignId('local_origem_id')->nullable()->constrained('locais')->nullOnDelete();
            $table->foreignId('local_destino_id')->nullable()->constrained('locais')->nullOnDelete();
            $table->dateTime('prazo_atendimento_demanda')->nullable();
            $table->dateTime('data_hora_inicio_carregamento')->nullable();
            $table->dateTime('data_hora_fim_carregamento')->nullable();
            $table->dateTime('data_hora_saida_origem')->nullable();
            $table->dateTime('data_hora_chegada_destino')->nullable();
            $table->dateTime('data_hora_inicio_descarregamento')->nullable();
            $table->dateTime('data_hora_fim_descarregamento')->nullable();
            $table->string('status_demanda')->default('pendente');
            $table->text('observacao_adicional')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandas');
    }
};
