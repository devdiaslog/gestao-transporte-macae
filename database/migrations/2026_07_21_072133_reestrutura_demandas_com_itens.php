<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reinício da base de demandas: a estrutura antiga (origem/destino no
        // cabeçalho) não é compatível com o modelo de itens de demanda.
        DB::table('demandas')->delete();

        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn(['origem', 'destino', 'prazo_referencia']);
        });

        Schema::table('demandas', function (Blueprint $table) {
            $table->string('fonte_demanda')->nullable()->after('tipo_cadastro');
            $table->dateTime('prazo_demanda')->nullable()->after('documento_demanda');
        });

        Schema::create('demanda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demanda_id')->constrained('demandas')->cascadeOnDelete();

            // Identificação do item dentro da demanda: RT + item + subitem
            $table->string('numero_rt');
            $table->string('numero_item');
            $table->string('subitem')->nullable();

            $table->string('local_origem')->nullable();
            $table->string('local_destino')->nullable();
            $table->string('descricao_local_retirada')->nullable();
            $table->text('descricao_item')->nullable();
            $table->string('status_item')->nullable();
            $table->dateTime('prazo_item')->nullable();

            $table->timestamps();

            $table->unique(['demanda_id', 'numero_rt', 'numero_item', 'subitem'], 'demanda_itens_identificacao_unique');
            $table->index('prazo_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demanda_itens');

        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn(['fonte_demanda', 'prazo_demanda']);
        });

        Schema::table('demandas', function (Blueprint $table) {
            $table->string('origem', 500)->nullable();
            $table->string('destino', 500)->nullable();
            $table->dateTime('prazo_referencia')->nullable();
        });
    }
};
