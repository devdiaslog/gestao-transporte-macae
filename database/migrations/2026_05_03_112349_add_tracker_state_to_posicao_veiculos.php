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
        Schema::table('posicao_veiculos', function (Blueprint $table) {
            $table->string('tracker_state')->nullable()->after('synced_at');   // Parado | Em Movimento | Sem Sinal
            $table->timestamp('state_since')->nullable()->after('tracker_state'); // quando entrou no estado atual
        });
    }

    public function down(): void
    {
        Schema::table('posicao_veiculos', function (Blueprint $table) {
            $table->dropColumn(['tracker_state', 'state_since']);
        });
    }
};
