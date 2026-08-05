<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cidade de cada ponta do trecho.
 *
 * Texto livre por ora, informado pela operação: o nome do local no SAP nem
 * sempre revela onde ele fica, e a cidade ajuda a conferir se a distância
 * cadastrada faz sentido. Não entra na identidade do trecho — quem a define
 * continua sendo o par origem→destino.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trechos_sap', function (Blueprint $table) {
            $table->string('cidade_origem')->nullable()->after('origem_sap');
            $table->string('cidade_destino')->nullable()->after('destino_sap');
        });
    }

    public function down(): void
    {
        Schema::table('trechos_sap', function (Blueprint $table) {
            $table->dropColumn(['cidade_origem', 'cidade_destino']);
        });
    }
};
