<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pendência do item faltoso (status 10).
 *
 * O transporte é nosso, mas o solicitante precisa acertar algo no pedido —
 * detalhar itens, informar destino, indicar a pessoa de contato. O motivo é
 * sempre nosso: o SAP não o transmite, então item que chega como 10 pela
 * importação fica sem motivo até alguém registrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->text('faltoso_motivo')->nullable()->after('status_sap');
            // Início da espera: o usuário informa (padrão, o instante do registro).
            $table->dateTime('faltoso_desde')->nullable()->after('faltoso_motivo');
            $table->foreignId('faltoso_por')->nullable()->after('faltoso_desde')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('faltoso_por');
            $table->dropColumn(['faltoso_motivo', 'faltoso_desde']);
        });
    }
};
