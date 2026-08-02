<?php

namespace App\Models;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusItemDemanda;
use App\Enums\StatusSap;
use App\Support\NormalizadorLocal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Item de entrega.
 *
 * Nasce no SAP quando o cliente libera a RT (status 03), quando ainda não há
 * atendimento — por isso `demanda_id` é opcional. A demanda é atribuída quando
 * o transporte é programado (status 04).
 */
class DemandaItem extends Model
{
    protected $table = 'demanda_itens';

    protected $fillable = [
        'demanda_id',
        'numero_rt',
        'numero_item',
        'subitem',
        'local_origem',
        'local_destino',
        'descricao_local_retirada',
        'descricao_item',
        'peso_total',
        'altura',
        'largura',
        'comprimento',
        'status_item',
        'status_sap',
        'prazo_item',
        'data_hora_entrega',
        'observacao',
        'campos_editados',
        'data_hora_criacao_rt',
        'data_hora_liberacao_rt',
        'doc_unitizacao_superior',
        'grupo_planejamento',
        'data_hora_previsao_entrega',
        'fora_escopo',
        'fora_escopo_justificativa',
        'fora_escopo_por',
        'fora_escopo_em',
        'ausente_no_sap_em',
    ];

    /**
     * Campos sincronizados do SAP que o operador pode assumir; quando presentes
     * em campos_editados, a importação deixa de sincronizá-los neste item.
     * Inclui o status e a entrega: o SAP os atualiza livremente até a torre
     * alterá-los pela interface.
     *
     * @var array<int, string>
     */
    public const CAMPOS_SINCRONIZADOS = [
        'local_origem',
        'local_destino',
        'descricao_local_retirada',
        'descricao_item',
        'prazo_item',
        'status_item',
        'data_hora_entrega',
    ];

    protected function casts(): array
    {
        return [
            'status_item' => StatusItemDemanda::class,
            'status_sap' => StatusSap::class,
            'prazo_item' => 'datetime',
            'data_hora_entrega' => 'datetime',
            'campos_editados' => 'array',
            'data_hora_criacao_rt' => 'datetime',
            'data_hora_liberacao_rt' => 'datetime',
            'data_hora_previsao_entrega' => 'datetime',
            'fora_escopo' => 'boolean',
            'fora_escopo_em' => 'datetime',
            'ausente_no_sap_em' => 'datetime',
        ];
    }

    /**
     * Mantém a forma canônica dos locais sincronizada, venha o dado da
     * importação, da API ou do ajuste manual do operador.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            if ($item->isDirty('local_origem')) {
                $item->local_origem_norm = NormalizadorLocal::canonizar($item->local_origem);
            }

            if ($item->isDirty('local_destino')) {
                $item->local_destino_norm = NormalizadorLocal::canonizar($item->local_destino);
            }
        });
    }

    public function campoEditadoPeloOperador(string $campo): bool
    {
        return in_array($campo, $this->campos_editados ?? [], true);
    }

    /**
     * Acrescenta texto à observação do item (histórico acumulativo): mantém o
     * conteúdo anterior, pula uma linha e adiciona o novo. Texto vazio ou já
     * presente na observação é ignorado para reimportações não duplicarem.
     */
    public function acrescentarObservacao(?string $texto): void
    {
        $texto = trim((string) $texto);

        if ($texto === '' || str_contains((string) $this->observacao, $texto)) {
            return;
        }

        $this->observacao = $this->observacao === null || trim($this->observacao) === ''
            ? $texto
            : rtrim($this->observacao)."\n\n".$texto;
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class);
    }

    public function previsoes(): HasMany
    {
        return $this->hasMany(DemandaItemPrevisao::class)->latest();
    }

    public function previsaoAtual(): HasOne
    {
        return $this->hasOne(DemandaItemPrevisao::class)->latestOfMany();
    }

    public function marcadoForaDoEscopoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fora_escopo_por');
    }

    /**
     * Prazo ainda alcançável (não vencido) na data corrente.
     */
    public function prazoExequivel(): bool
    {
        return $this->prazo_item !== null && $this->prazo_item->isFuture();
    }

    /**
     * Registra uma previsão de entrega, guardando-a no histórico e deixando o
     * item com a data vigente. Previsão idêntica à atual não gera nova linha.
     */
    public function registrarPrevisao(
        \DateTimeInterface $previsao,
        OrigemPrevisao $origem = OrigemPrevisao::Manual,
        ?int $usuarioId = null,
        ?string $motivo = null,
    ): ?DemandaItemPrevisao {
        if ($this->data_hora_previsao_entrega?->equalTo($previsao)) {
            return null;
        }

        $registro = $this->previsoes()->create([
            'data_hora_previsao' => $previsao,
            'origem' => $origem,
            'definido_por' => $usuarioId,
            'motivo' => $motivo,
        ]);

        $this->forceFill(['data_hora_previsao_entrega' => $previsao])->save();

        return $registro;
    }

    /**
     * Marca o item como fora da nossa responsabilidade, exigindo justificativa.
     */
    public function marcarForaDoEscopo(string $justificativa, int $usuarioId): void
    {
        $this->forceFill([
            'fora_escopo' => true,
            'fora_escopo_justificativa' => $justificativa,
            'fora_escopo_por' => $usuarioId,
            'fora_escopo_em' => now(),
        ])->save();
    }

    public function reverterForaDoEscopo(): void
    {
        $this->forceFill([
            'fora_escopo' => false,
            'fora_escopo_justificativa' => null,
            'fora_escopo_por' => null,
            'fora_escopo_em' => null,
        ])->save();
    }

    /**
     * O cliente ainda cobra este item: não foi atendido, cancelado, suspenso
     * nem marcado como fora do nosso escopo.
     */
    public function emCobranca(): bool
    {
        return ! $this->fora_escopo && ($this->status_sap?->emCobranca() ?? false);
    }

    /**
     * Situação da previsão diante do prazo — é o semáforo que o cliente enxerga.
     *
     * @return 'sem_prazo'|'sem_previsao'|'no_prazo'|'fora_do_prazo'
     */
    public function situacaoPrevisao(): string
    {
        if ($this->prazo_item === null) {
            return 'sem_prazo';
        }

        if ($this->data_hora_previsao_entrega === null) {
            return 'sem_previsao';
        }

        return $this->data_hora_previsao_entrega->lessThanOrEqualTo($this->prazo_item)
            ? 'no_prazo'
            : 'fora_do_prazo';
    }
}
