<?php

namespace App\Models;

use App\Enums\PrazoPadrao;
use App\Support\NormalizadorLocal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Prazo acordado para um trecho origem→destino.
 *
 * O par de locais é a identidade do trecho, na forma canônica — a mesma
 * normalização dos itens, para que "ARM-MACAE" e "ARM MACAÉ" encontrem o
 * mesmo prazo.
 */
class TrechoSap extends Model
{
    use SoftDeletes;

    protected $table = 'trechos_sap';

    protected $fillable = [
        'origem_sap',
        'destino_sap',
        'chave_origem_destino',
        'km_trecho',
        'prazo_horas_normal',
        'prazo_horas_expresso',
        'prazo_padrao',
        'atualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'km_trecho' => 'float',
            'prazo_horas_normal' => 'integer',
            'prazo_horas_expresso' => 'integer',
            'prazo_padrao' => PrazoPadrao::class,
        ];
    }

    /**
     * Mantém a chave em sincronia com os locais, venha o dado do formulário,
     * da planilha ou de um ajuste em massa.
     */
    protected static function booted(): void
    {
        static::saving(function (self $trecho) {
            if ($trecho->isDirty(['origem_sap', 'destino_sap'])) {
                $trecho->chave_origem_destino = self::chaveDe($trecho->origem_sap, $trecho->destino_sap);
            }
        });
    }

    /**
     * Identidade canônica do trecho: origem e destino normalizados.
     */
    public static function chaveDe(?string $origem, ?string $destino): string
    {
        return NormalizadorLocal::canonizar($origem).' > '.NormalizadorLocal::canonizar($destino);
    }

    /**
     * Horas de prazo que valem para este trecho.
     *
     * "Manual" ainda cai no normal: a regra que o diferencia depende do tipo
     * do item e será definida pela operação.
     */
    public function horasVigentes(): ?int
    {
        return match ($this->prazo_padrao) {
            PrazoPadrao::Expresso => $this->prazo_horas_expresso ?? $this->prazo_horas_normal,
            default => $this->prazo_horas_normal,
        };
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por');
    }

    /**
     * Busca por qualquer ponta do trecho ou pela chave.
     */
    public function scopeBusca(Builder $query, ?string $termo): Builder
    {
        if (blank($termo)) {
            return $query;
        }

        $termo = trim($termo);

        return $query->where(fn (Builder $q) => $q
            ->where('origem_sap', 'like', "%{$termo}%")
            ->orWhere('destino_sap', 'like', "%{$termo}%")
            ->orWhere('chave_origem_destino', 'like', '%'.NormalizadorLocal::canonizar($termo).'%'));
    }
}
