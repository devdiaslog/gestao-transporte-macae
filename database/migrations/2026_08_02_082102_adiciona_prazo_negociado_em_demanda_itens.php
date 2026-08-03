<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prazo renegociado com o cliente.
 *
 * Nem todo atraso é atraso: um prazo pode ser renegociado, e nesse caso marcar
 * o item como "fora do prazo" contra a data original seria falso alarme. O
 * operador passa a poder alterar o prazo, e a partir daí ele vale sobre o do
 * SAP.
 *
 * `prazo_sap` guarda o que o SAP mandou, sempre — assim a tela mostra os dois
 * e fica visível que houve renegociação, em vez de o dado original sumir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dateTime('prazo_sap')->nullable()->after('prazo_item');
            $table->text('prazo_motivo')->nullable()->after('prazo_sap');
            $table->foreignId('prazo_alterado_por')->nullable()->after('prazo_motivo')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('prazo_alterado_em')->nullable()->after('prazo_alterado_por');
        });

        // Nos itens já cadastrados o prazo atual é o que veio do SAP: nenhum
        // foi renegociado antes desta funcionalidade existir.
        DB::table('demanda_itens')->whereNotNull('prazo_item')->update([
            'prazo_sap' => DB::raw('prazo_item'),
        ]);
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prazo_alterado_por');
            $table->dropColumn(['prazo_sap', 'prazo_motivo', 'prazo_alterado_em']);
        });
    }
};
