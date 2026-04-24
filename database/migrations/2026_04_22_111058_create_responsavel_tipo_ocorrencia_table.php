<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsavel_tipo_ocorrencia', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo');
            $table->unsignedBigInteger('id_responsavel');

            $table->primary(['id_tipo', 'id_responsavel']);

            $table->foreign('id_tipo')
                ->references('id_tipo')
                ->on('tipos_ocorrencia')
                ->cascadeOnDelete();

            $table->foreign('id_responsavel')
                ->references('id_responsavel')
                ->on('responsaveis')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsavel_tipo_ocorrencia');
    }
};
