<?php

use App\Models\DuracaoRota;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quanto tempo leva atender uma rota, estimado pela operação.
 *
 * É o dado que falta para responder "consigo entregar os dois no prazo ou só
 * um?": sem duração não há como saber o que cabe. Enquanto a rota não for
 * estimada, vale o padrão de {@see DuracaoRota::HORAS_PADRAO}.
 *
 * A chave é a forma canônica dos locais, a mesma que agrupa os itens na tela —
 * "ARM-MACAE" e "ARM MACAÉ" são a mesma rota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duracao_rotas', function (Blueprint $table) {
            $table->id();
            $table->string('local_origem_norm');
            $table->string('local_destino_norm');
            $table->decimal('horas', 6, 1);
            $table->foreignId('atualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['local_origem_norm', 'local_destino_norm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duracao_rotas');
    }
};
