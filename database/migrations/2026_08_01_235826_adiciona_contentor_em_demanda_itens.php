<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Embalagem superior (unitização) do item.
 *
 * Quando o item viaja dentro de um contentor, quem ocupa o piso da carreta é o
 * contentor — as medidas das caixas lá dentro não importam para o
 * carregamento, e somá-las contaria o mesmo espaço várias vezes.
 *
 * O SAP entrega as medidas da unitização em colunas próprias. A área fica
 * gravada para o agrupamento por trecho poder somá-la em SQL.
 *
 * numero_contentor (padrão 30xxxxxx) é o contentor físico e não se confunde
 * com doc_unitizacao_superior (padrão 4xxxxxx), que é o documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->string('numero_contentor')->nullable()->after('doc_unitizacao_superior');
            $table->string('descricao_contentor')->nullable()->after('numero_contentor');

            $table->decimal('comprimento_embalagem', 10, 4)->nullable()->after('descricao_contentor');
            $table->decimal('largura_embalagem', 10, 4)->nullable()->after('comprimento_embalagem');
            $table->decimal('altura_embalagem', 10, 4)->nullable()->after('largura_embalagem');
            $table->decimal('area_embalagem', 10, 2)->nullable()->after('altura_embalagem');

            $table->index('numero_contentor');
        });
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropIndex(['numero_contentor']);
            $table->dropColumn([
                'numero_contentor',
                'descricao_contentor',
                'comprimento_embalagem',
                'largura_embalagem',
                'altura_embalagem',
                'area_embalagem',
            ]);
        });
    }
};
