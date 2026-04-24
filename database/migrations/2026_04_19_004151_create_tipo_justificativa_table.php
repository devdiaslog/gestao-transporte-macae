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
        Schema::create('tipo_justificativa', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo');
            $table->unsignedBigInteger('id_justificativa');

            $table->primary(['id_tipo', 'id_justificativa']);

            $table->foreign('id_tipo')
                ->references('id_tipo')
                ->on('tipos_ocorrencia')
                ->cascadeOnDelete();

            $table->foreign('id_justificativa')
                ->references('id_justificativa')
                ->on('justificativas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_justificativa');
    }
};
