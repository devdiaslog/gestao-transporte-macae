<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplia a duração dos eventos de cerca e de status.
 *
 * `unsignedSmallInteger` guarda até 65.535 minutos — 45 dias. Um evento que
 * fique aberto além disso faz o UPDATE estourar com "Out of range value", e
 * como o geofencing roda dentro da sincronização de posições, a exceção
 * derrubava a sincronização inteira: nenhum veículo era atualizado e, passadas
 * três horas, a frota toda aparecia como "sem sinal".
 *
 * `unsignedInteger` cobre ~8.100 anos, o que tira o teto do caminho.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cerca_eventos', function (Blueprint $table) {
            $table->unsignedInteger('duracao_minutos')->nullable()->change();
        });

        Schema::table('status_eventos', function (Blueprint $table) {
            $table->unsignedInteger('duracao_minutos')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cerca_eventos', function (Blueprint $table) {
            $table->unsignedSmallInteger('duracao_minutos')->nullable()->change();
        });

        Schema::table('status_eventos', function (Blueprint $table) {
            $table->unsignedSmallInteger('duracao_minutos')->nullable()->change();
        });
    }
};
