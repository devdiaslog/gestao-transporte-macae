<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rastro do prazo calculado pela tabela de trechos.
 *
 * Guarda qual trecho gerou o prazo e quando, para que a tela consiga explicar
 * de onde veio a data — e para que um trecho corrigido depois seja
 * reconhecível nos itens que ainda usam o prazo antigo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->foreignId('prazo_trecho_id')->nullable()->after('prazo_alterado_em')
                ->constrained('trechos_sap')->nullOnDelete();
            $table->dateTime('prazo_calculado_em')->nullable()->after('prazo_trecho_id');
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prazo_trecho_id');
            $table->dropColumn('prazo_calculado_em');
        });
    }
};
