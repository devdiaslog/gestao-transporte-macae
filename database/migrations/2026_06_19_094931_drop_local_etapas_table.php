<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('local_etapas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('local_etapas', function ($table) {
            $table->id();
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }
};
