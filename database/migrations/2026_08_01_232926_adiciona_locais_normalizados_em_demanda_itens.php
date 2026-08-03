<?php

use App\Support\NormalizadorLocal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda a forma canônica dos locais para agrupar itens por trecho.
 *
 * O agrupamento precisa acontecer em SQL — são milhares de itens — e a chave
 * normalizada persistida permite GROUP BY e índice. O valor original continua
 * intacto nas colunas local_origem e local_destino.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->string('local_origem_norm')->nullable()->after('local_origem');
            $table->string('local_destino_norm')->nullable()->after('local_destino');

            $table->index(['local_origem_norm', 'local_destino_norm'], 'demanda_itens_trecho_index');
        });

        $this->preencherExistentes();
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropIndex('demanda_itens_trecho_index');
            $table->dropColumn(['local_origem_norm', 'local_destino_norm']);
        });
    }

    /**
     * Normaliza os itens já cadastrados.
     *
     * Um UPDATE por valor distinto em vez de um por item: são dezenas de
     * grafias para milhares de linhas, então percorrer item a item levaria
     * minutos em produção sem nenhum ganho.
     */
    private function preencherExistentes(): void
    {
        foreach (['local_origem' => 'local_origem_norm', 'local_destino' => 'local_destino_norm'] as $origem => $destino) {
            $valores = DB::table('demanda_itens')
                ->whereNotNull($origem)
                ->distinct()
                ->pluck($origem);

            foreach ($valores as $valor) {
                DB::table('demanda_itens')
                    ->where($origem, $valor)
                    ->update([$destino => NormalizadorLocal::canonizar($valor)]);
            }
        }
    }
};
