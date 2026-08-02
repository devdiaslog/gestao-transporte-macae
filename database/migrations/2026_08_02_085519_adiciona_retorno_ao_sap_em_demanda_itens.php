<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idas e vindas do item no SAP.
 *
 * O export é filtrado por status e por grupo de planejamento, então o item
 * some quando muda de grupo ou volta a um status anterior à liberação — e
 * reaparece quando é devolvido ao grupo e liberado de novo. É o mesmo item,
 * com a mesma chave, mas quem prometeu uma data antes precisa saber que ele
 * ficou fora do radar nesse intervalo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dateTime('retornou_ao_sap_em')->nullable()->after('ausente_no_sap_em');
            $table->unsignedSmallInteger('vezes_ausente')->default(0)->after('retornou_ao_sap_em');
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropColumn(['retornou_ao_sap_em', 'vezes_ausente']);
        });
    }
};
