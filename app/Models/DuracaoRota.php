<?php

namespace App\Models;

use App\Support\NormalizadorLocal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tempo estimado de atendimento de uma rota, informado pela operação.
 *
 * Serve para decidir o que cabe no prazo: sem saber quanto custa atender, não
 * há como dizer se dá para entregar dois pedidos ou apenas um.
 */
class DuracaoRota extends Model
{
    protected $table = 'duracao_rotas';

    /**
     * Estimativa usada enquanto a rota não for medida pela operação.
     *
     * Um dia inteiro é deliberadamente conservador: superestimar faz o
     * sequenciamento recomendar menos entregas do que caberiam, o que é
     * preferível a prometer o que não dá.
     */
    public const HORAS_PADRAO = 24.0;

    protected $fillable = [
        'local_origem_norm',
        'local_destino_norm',
        'horas',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'horas' => 'float',
        ];
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por');
    }

    /**
     * Duração de cada rota já cadastrada, indexada por "origem|destino".
     *
     * @return array<string, float>
     */
    public static function mapa(): array
    {
        return self::query()
            ->get(['local_origem_norm', 'local_destino_norm', 'horas'])
            ->mapWithKeys(fn (self $d) => [
                $d->local_origem_norm.'|'.$d->local_destino_norm => (float) $d->horas,
            ])
            ->all();
    }

    /**
     * Grava a estimativa da rota, canonizando os locais como a tela faz.
     */
    public static function definir(?string $origem, ?string $destino, float $horas, ?int $usuarioId): self
    {
        return self::updateOrCreate(
            [
                'local_origem_norm' => NormalizadorLocal::canonizar($origem),
                'local_destino_norm' => NormalizadorLocal::canonizar($destino),
            ],
            [
                'horas' => $horas,
                'atualizado_por' => $usuarioId,
            ],
        );
    }
}
