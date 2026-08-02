<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prepara o item de entrega para existir antes da demanda.
 *
 * O item nasce no SAP quando o cliente o libera (status 03) — nesse momento
 * ainda não há atendimento/viagem, então `demanda_id` passa a aceitar nulo e o
 * item é adotado por uma demanda quando o transporte é programado (status 04).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('demanda_id')->nullable()->change();
        });

        Schema::table('demanda_itens', function (Blueprint $table) {
            // Datas da RT — o SAP entrega data e hora separadas, guardamos unidas.
            $table->dateTime('data_hora_criacao_rt')->nullable()->after('status_sap');
            $table->dateTime('data_hora_liberacao_rt')->nullable()->after('data_hora_criacao_rt');

            // Embalagem superior (contentor): agrupa várias RTs para o mesmo destino.
            $table->string('doc_unitizacao_superior')->nullable()->after('data_hora_liberacao_rt');
            $table->string('grupo_planejamento')->nullable()->after('doc_unitizacao_superior');

            // Previsão vigente; o histórico completo vive em demanda_item_previsoes.
            $table->dateTime('data_hora_previsao_entrega')->nullable()->after('grupo_planejamento');

            // Itens que não são de nossa responsabilidade atender.
            $table->boolean('fora_escopo')->default(false)->after('data_hora_previsao_entrega');
            $table->text('fora_escopo_justificativa')->nullable()->after('fora_escopo');
            $table->foreignId('fora_escopo_por')->nullable()->after('fora_escopo_justificativa')
                ->constrained('users')->nullOnDelete();
            $table->dateTime('fora_escopo_em')->nullable()->after('fora_escopo_por');

            // Item que sumiu do export sem aparecer na visão de viagem: vai para
            // conferência do operador em vez de ter o status alterado sozinho.
            $table->dateTime('ausente_no_sap_em')->nullable()->after('fora_escopo_em');

            $table->index('doc_unitizacao_superior');
            $table->index('data_hora_previsao_entrega');

            // Busca do item pela chave natural do SAP. Não é único: o unique
            // existente inclui demanda_id, e com itens sem demanda o banco não
            // consegue impedir duplicatas (NULL nunca é igual a NULL). A
            // unicidade dos itens sem demanda é garantida na importação.
            $table->index(['numero_rt', 'numero_item', 'subitem'], 'demanda_itens_chave_sap_index');
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropIndex('demanda_itens_chave_sap_index');
            $table->dropIndex(['data_hora_previsao_entrega']);
            $table->dropIndex(['doc_unitizacao_superior']);

            $table->dropConstrainedForeignId('fora_escopo_por');
            $table->dropColumn([
                'data_hora_criacao_rt',
                'data_hora_liberacao_rt',
                'doc_unitizacao_superior',
                'grupo_planejamento',
                'data_hora_previsao_entrega',
                'fora_escopo',
                'fora_escopo_justificativa',
                'fora_escopo_em',
                'ausente_no_sap_em',
            ]);
        });

        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->unsignedBigInteger('demanda_id')->nullable(false)->change();
        });
    }
};
