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
        Schema::create('modelos_equipamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_equipamento_id')->constrained('tipos_equipamentos')->restrictOnDelete();
            $table->string('nome');
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
        Schema::dropIfExists('modelos_equipamentos');
    }
};
