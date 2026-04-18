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
        Schema::create('equipamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_id')->constrained('tipos_equipamentos')->restrictOnDelete();
            $table->foreignId('modelo_id')->nullable()->constrained('modelos_equipamentos')->nullOnDelete();
            $table->foreignId('divisao_id')->nullable()->constrained('divisoes')->nullOnDelete();
            $table->foreignId('sub_divisao_id')->nullable()->constrained('sub_divisoes')->nullOnDelete();
            $table->string('id_elog')->nullable();
            $table->string('prefixo')->nullable();
            $table->string('placa');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipamentos');
    }
};
