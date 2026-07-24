<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rastreio do atendimento no E-log/TMS: início (primeira vez que a demanda
     * é vista numa captura) e fim (quando deixa de aparecer, indicando que foi
     * concluída no TMS por qualquer operador). A tabela de captura guarda a
     * primeira e a última presença por demanda.
     */
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dateTime('data_hora_inicio_elog')->nullable()->after('fim_automatico');
            $table->dateTime('data_hora_fim_elog')->nullable()->after('data_hora_inicio_elog');
        });

        Schema::create('demanda_captura_elog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demanda_id')->constrained('demandas')->cascadeOnDelete();
            $table->dateTime('primeira_captura');
            $table->dateTime('ultima_captura');
            $table->timestamps();

            $table->unique('demanda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demanda_captura_elog');

        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn(['data_hora_inicio_elog', 'data_hora_fim_elog']);
        });
    }
};
