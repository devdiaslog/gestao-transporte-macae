<?php

use App\Models\DemandaItem;
use App\Services\DemandaCalculadora;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->string('tipo_item', 20)->nullable()->after('descricao_item');
            $table->boolean('tipo_item_manual')->default(false)->after('tipo_item');
        });

        $chaves = DemandaCalculadora::PONTOS_CHAVE;

        // Classifica o acervo pelo mesmo critério do model, em três UPDATEs,
        // na ordem em que os casos se excluem.
        DemandaItem::query()
            ->whereIn('local_destino_norm', $chaves)
            ->update(['tipo_item' => 'load']);

        DemandaItem::query()
            ->whereNull('tipo_item')
            ->whereIn('local_origem_norm', $chaves)
            ->update(['tipo_item' => 'backload']);

        DemandaItem::query()
            ->whereNull('tipo_item')
            ->where(function ($query) {
                $query->whereNotNull('local_origem_norm')->orWhereNotNull('local_destino_norm');
            })
            ->update(['tipo_item' => 'transferencia']);
    }

    public function down(): void
    {
        Schema::table('demanda_itens', function (Blueprint $table) {
            $table->dropColumn(['tipo_item', 'tipo_item_manual']);
        });
    }
};
