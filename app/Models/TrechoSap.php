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

    /**
     * Garante que existe um trecho para cada rota informada, criando os que
     * faltam sem km e sem prazo.
     *
     * A importação de itens descobre rotas que ninguém cadastrou. Criar o
     * esqueleto na hora transforma isso numa lista de pendências visível em
     * Cadastros — melhor do que a equipe descobrir rota a rota, item a item,
     * ao tentar calcular prazo.
     *
     * O que depende de decisão humana fica em branco de propósito: distância e
     * prazo não podem ser inventados.
     *
     * @param  iterable<int, array{origem: ?string, destino: ?string}>  $rotas
     * @return int quantos trechos foram criados
     */
    public static function garantirRotas(iterable $rotas): int
    {
        $novos = [];

        foreach ($rotas as $rota) {
            $origem = $rota['origem'] ?? null;
            $destino = $rota['destino'] ?? null;

            if (blank($origem) || blank($destino)) {
                continue;
            }

            $novos[self::chaveDe($origem, $destino)] ??= [
                'origem_sap' => $origem,
                'destino_sap' => $destino,
            ];
        }

        if ($novos === []) {
            return 0;
        }

        // Trecho excluído continua ocupando a chave: reaparecer a rota não o
        // ressuscita nem cria um duplicado.
        $existentes = self::withTrashed()
            ->whereIn('chave_origem_destino', array_keys($novos))
            ->pluck('chave_origem_destino')
            ->all();

        $faltantes = array_diff_key($novos, array_flip($existentes));

        if ($faltantes === []) {
            return 0;
        }

        $agora = now();

        self::insert(array_map(fn (array $dados, string $chave) => [
            'origem_sap' => $dados['origem_sap'],
            'destino_sap' => $dados['destino_sap'],
            'chave_origem_destino' => $chave,
            'prazo_padrao' => PrazoPadrao::Normal->value,
            'created_at' => $agora,
            'updated_at' => $agora,
        ], $faltantes, array_keys($faltantes)));

        return count($faltantes);
    }

    /**
     * O trecho ainda não tem o que a operação precisa preencher.
     */
    public function incompleto(): bool
    {
        return $this->km_trecho === null || $this->horasVigentes() === null;
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
