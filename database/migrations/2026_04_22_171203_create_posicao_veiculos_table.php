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
        Schema::create('posicao_veiculos', function (Blueprint $table) {
            $table->id();
            $table->string('license_plate')->unique();       // chave natural — upsert por placa
            $table->string('prefix')->nullable();
            $table->string('chassis')->nullable();
            $table->dateTimeTz('position_at')->nullable();   // dateTime da API (com fuso horário)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('speed')->nullable();
            $table->boolean('ignition')->nullable();
            $table->unsignedSmallInteger('direction')->nullable();
            $table->unsignedBigInteger('hodometer')->nullable();
            $table->string('gps_status')->nullable();
            $table->boolean('gsm_status')->nullable();
            $table->string('event_type')->nullable();
            $table->string('transmission_way')->nullable();
            $table->string('rfid')->nullable();
            $table->timestamp('synced_at')->nullable();      // momento da última sincronização
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posicao_veiculos');
    }
};
