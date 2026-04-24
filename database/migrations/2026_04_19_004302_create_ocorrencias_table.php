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
        Schema::create('ocorrencias', function (Blueprint $table) {
            $table->id('id_ocorrencia');

            $table->unsignedBigInteger('id_veiculo');
            $table->unsignedBigInteger('id_tipo');
            $table->unsignedBigInteger('id_responsavel')->nullable();
            $table->unsignedBigInteger('id_justificativa')->nullable();

            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim')->nullable();
            $table->text('observacao')->nullable();

            $table->timestamps();

            $table->foreign('id_veiculo')
                ->references('id')
                ->on('equipamentos')
                ->restrictOnDelete();

            $table->foreign('id_tipo')
                ->references('id_tipo')
                ->on('tipos_ocorrencia')
                ->restrictOnDelete();

            $table->foreign('id_responsavel')
                ->references('id_responsavel')
                ->on('responsaveis')
                ->nullOnDelete();

            $table->foreign('id_justificativa')
                ->references('id_justificativa')
                ->on('justificativas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocorrencias');
    }
};
