<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prazo acordado por trecho — a tabela de apoio que diz quanto tempo cada
 * origem→destino tem para ser cumprido.
 *
 * A chave é a forma canônica do par, a mesma normalização usada nos itens:
 * "ARM-MACAE" e "ARM MACAÉ" são o mesmo lugar, e o trecho é um só.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trechos_sap', function (Blueprint $table) {
            $table->id();

            // Como o SAP escreve, preservado para a operação reconhecer.
            $table->string('origem_sap');
            $table->string('destino_sap');

            // Forma canônica do par: é por ela que o item encontra seu prazo.
            $table->string('chave_origem_destino')->unique();

            $table->decimal('km_trecho', 8, 1)->nullable();
            $table->unsignedSmallInteger('prazo_horas_normal')->nullable();
            $table->unsignedSmallInteger('prazo_horas_expresso')->nullable();
            $table->string('prazo_padrao', 20)->default('automatico');

            $table->foreignId('atualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['origem_sap', 'destino_sap']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trechos_sap');
    }
};
