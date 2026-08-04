<?php

namespace App\Models;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusItemDemanda;
use App\Enums\StatusSap;
use App\Enums\TipoDemanda;
use App\Services\DemandaCalculadora;
use App\Support\ContentorSap;
use App\Support\NormalizadorLocal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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
        'tipo_item',
        'tipo_item_manual',
        'peso_total',
        'altura',
        'largura',
        'comprimento',
        'status_item',
        'status_sap',
        'faltoso_motivo',
        'faltoso_desde',
        'faltoso_por',
        'prazo_item',
        'prazo_sap',
        'prazo_motivo',
        'prazo_alterado_por',
        'prazo_alterado_em',
        'data_hora_entrega',
        'observacao',
        'campos_editados',
        'data_hora_criacao_rt',
        'data_hora_liberacao_rt',
        'doc_unitizacao_superior',
        'numero_contentor',
        'descricao_contentor',
        'comprimento_embalagem',
        'largura_embalagem',
        'altura_embalagem',
        'area_embalagem',
        'grupo_planejamento',
        'data_hora_previsao_entrega',
        'fora_escopo',
        'fora_escopo_justificativa',
        'fora_escopo_por',
        'fora_escopo_em',
        'ausente_no_sap_em',
        'retornou_ao_sap_em',
        'vezes_ausente',
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
            'tipo_item' => TipoDemanda::class,
            'tipo_item_manual' => 'boolean',
            'status_sap' => StatusSap::class,
            'faltoso_desde' => 'datetime',
            'prazo_item' => 'datetime',
            'prazo_sap' => 'datetime',
            'prazo_alterado_em' => 'datetime',
            'data_hora_entrega' => 'datetime',
            'campos_editados' => 'array',
            'data_hora_criacao_rt' => 'datetime',
            'data_hora_liberacao_rt' => 'datetime',
            'data_hora_previsao_entrega' => 'datetime',
            'fora_escopo' => 'boolean',
            'fora_escopo_em' => 'datetime',
            'ausente_no_sap_em' => 'datetime',
            'retornou_ao_sap_em' => 'datetime',
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

            // O tipo acompanha a rota enquanto o usuário não o fixar na mão.
            if (! $item->tipo_item_manual && $item->isDirty(['local_origem', 'local_destino'])) {
                $item->tipo_item = $item->tipoPelaRota();
            }

            // Área de piso da unitização, das colunas do SAP ou, na falta
            // delas, do que a descrição do contentor traz no texto.
            if ($item->isDirty(['comprimento_embalagem', 'largura_embalagem', 'descricao_contentor'])) {
                $item->area_embalagem = ContentorSap::area(
                    $item->comprimento_embalagem !== null ? (float) $item->comprimento_embalagem : null,
                    $item->largura_embalagem !== null ? (float) $item->largura_embalagem : null,
                    $item->descricao_contentor,
                );
            }
        });
    }

    /**
     * Tipo deduzido da rota do item, pelo mesmo critério usado na demanda:
     * ponto-chave no destino é Load, na origem é Backload, e o restante é
     * transferência. Sem rota, o tipo fica indefinido.
     */
    public function tipoPelaRota(): ?TipoDemanda
    {
        $origem = NormalizadorLocal::canonizar($this->local_origem);
        $destino = NormalizadorLocal::canonizar($this->local_destino);

        if ($origem === null && $destino === null) {
            return null;
        }

        if (in_array($destino, DemandaCalculadora::PONTOS_CHAVE, true)) {
            return TipoDemanda::Load;
        }

        if (in_array($origem, DemandaCalculadora::PONTOS_CHAVE, true)) {
            return TipoDemanda::Backload;
        }

        return TipoDemanda::Transferencia;
    }

    /**
     * Fixa o tipo informado pelo usuário; a partir daí a rota deixa de mandar
     * nele. Sem tipo, o item volta a acompanhar a rota.
     */
    public function definirTipo(?TipoDemanda $tipo): void
    {
        $this->tipo_item_manual = $tipo !== null;
        $this->tipo_item = $tipo ?? $this->tipoPelaRota();
        $this->save();
    }

    /**
     * Tempo mínimo de espera com o solicitante antes de o item poder virar
     * suspensão de responsabilidade dele (18).
     */
    public const HORAS_DE_ESPERA_FALTOSO = 48;

    public function faltoso(): bool
    {
        return $this->status_sap?->faltoso() ?? false;
    }

    /**
     * Instante em que a espera com o solicitante se esgota.
     */
    public function esperaFaltosoAte(): ?Carbon
    {
        return $this->faltoso_desde?->copy()->addHours(self::HORAS_DE_ESPERA_FALTOSO);
    }

    /**
     * A espera acabou e ninguém acertou a pendência: o item está liberado
     * para virar 18. A decisão continua sendo de uma pessoa — o sistema
     * apenas sinaliza.
     */
    public function esperaFaltosoVencida(): bool
    {
        $limite = $this->esperaFaltosoAte();

        return $this->faltoso() && $limite !== null && $limite->isPast();
    }

    /**
     * Registra a pendência que trava o item, com o instante em que ela começou
     * a correr — o usuário informa, e o padrão é o momento do registro.
     */
    public function marcarFaltoso(string $motivo, ?Carbon $desde, ?int $usuarioId): void
    {
        $this->status_sap = StatusSap::Faltoso;
        $this->faltoso_motivo = $motivo;
        $this->faltoso_desde = $desde ?? now();
        $this->faltoso_por = $usuarioId;

        // O status passa a ser nosso: a importação para de sobrescrevê-lo.
        $this->marcarCampoEditado('status_item');
        $this->save();
    }

    private function marcarCampoEditado(string $campo): void
    {
        $editados = $this->campos_editados ?? [];

        if (! in_array($campo, $editados, true)) {
            $editados[] = $campo;
            $this->campos_editados = array_values($editados);
        }
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
     * Registra um prazo renegociado com o cliente.
     *
     * Nem todo atraso é atraso: quando o prazo é renegociado, é o novo que vale
     * — cobrar o item contra a data original mostraria uma realidade que não
     * existe mais. O campo passa a ser do operador, então a importação seguinte
     * não o desfaz, e o prazo original do SAP continua guardado para que a
     * renegociação fique visível.
     */
    public function renegociarPrazo(\DateTimeInterface $prazo, int $usuarioId, ?string $motivo = null): void
    {
        $editados = $this->campos_editados ?? [];
        $editados[] = 'prazo_item';

        $this->forceFill([
            'prazo_item' => $prazo,
            'prazo_motivo' => $motivo,
            'prazo_alterado_por' => $usuarioId,
            'prazo_alterado_em' => now(),
            'campos_editados' => array_values(array_unique($editados)),
        ])->save();
    }

    /**
     * O prazo em vigor difere do que o SAP mandou.
     */
    public function prazoRenegociado(): bool
    {
        return $this->prazo_alterado_em !== null
            && $this->prazo_sap !== null
            && $this->prazo_item !== null
            && ! $this->prazo_item->equalTo($this->prazo_sap);
    }

    public function prazoAlteradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prazo_alterado_por');
    }

    /**
     * O item saiu do export e voltou.
     *
     * Acontece quando muda de grupo de planejamento ou volta a um status
     * anterior à liberação: some do filtro, e reaparece quando é devolvido ao
     * grupo e liberado de novo.
     */
    public function voltouAoSap(): bool
    {
        return $this->retornou_ao_sap_em !== null;
    }

    /**
     * Voltou trazendo de volta uma previsão prometida antes de sumir.
     *
     * É o caso que pede atenção: o item ficou fora do radar e a data que o
     * cliente recebeu provavelmente não vale mais.
     */
    public function previsaoAnteriorAoRetorno(): bool
    {
        return $this->voltouAoSap()
            && $this->data_hora_previsao_entrega !== null
            && $this->previsaoAtual !== null
            && $this->previsaoAtual->created_at->lessThan($this->retornou_ao_sap_em);
    }

    /**
     * Acima disto a medida não é de transporte rodoviário: uma carreta tem
     * cerca de 30 m² de piso, então um item sozinho passando de 100 m² veio
     * do SAP em outra unidade — centímetros ou milímetros em vez de metros.
     */
    private const AREA_MAXIMA_PLAUSIVEL = 100.0;

    /**
     * Área ocupada no piso da carreta pelas medidas do próprio item, em m².
     *
     * Só comprimento × largura: a altura não disputa espaço de piso, ela entra
     * na classificação do porte.
     */
    public function area(): ?float
    {
        if (! $this->comprimento || ! $this->largura) {
            return null;
        }

        return round((float) $this->comprimento * (float) $this->largura, 2);
    }

    /**
     * Medida que não pode estar em metros.
     *
     * O item continua aparecendo com o valor que o SAP mandou — o dado não é
     * corrigido por adivinhação — mas fica de fora dos somatórios, que de
     * outro modo ficariam inutilizáveis, e é sinalizado para conferência.
     */
    public function medidaSuspeita(): bool
    {
        $area = $this->dentroDeEmbalagem()
            ? ($this->area_embalagem !== null ? (float) $this->area_embalagem : null)
            : $this->area();

        return $area !== null && $area > self::AREA_MAXIMA_PLAUSIVEL;
    }

    /**
     * Área que o item de fato ocupa no veículo, em m².
     *
     * Dentro de uma embalagem superior, quem ocupa o piso é ela — as medidas
     * do que está dentro não importam para o carregamento. Ao somar vários
     * itens da mesma embalagem, essa área deve ser contada uma única vez.
     */
    public function areaEfetiva(): ?float
    {
        if ($this->dentroDeEmbalagem()) {
            return $this->area_embalagem !== null ? (float) $this->area_embalagem : null;
        }

        return $this->area();
    }

    /**
     * Identificação da embalagem superior em que o item viaja.
     *
     * O documento de unitização é o código da embalagem — que pode ser um
     * contentor, mas também uma caixa de madeira ou um pallet: duas RTs podem
     * dividir a mesma embalagem sem estar dentro de contentor nenhum. É ele que
     * agrupa os itens no status 03; o número do contentor só aparece no export
     * de viagem e serve de reserva para o item que não passou pela liberação.
     *
     * O número é gerado pelo SAP e muda quando há desliberação e nova
     * unitização. Não é identidade estável — o que mantém a visão correta é a
     * re-sincronização a cada importação.
     */
    public function embalagemSuperior(): ?string
    {
        foreach ([$this->doc_unitizacao_superior, $this->numero_contentor] as $identificacao) {
            if ($identificacao !== null && $identificacao !== '') {
                return (string) $identificacao;
            }
        }

        return null;
    }

    public function dentroDeEmbalagem(): bool
    {
        return $this->embalagemSuperior() !== null;
    }

    /**
     * Dimensões formatadas como "C × L × A" em metros.
     */
    public function dimensoes(): ?string
    {
        if (! $this->comprimento && ! $this->largura && ! $this->altura) {
            return null;
        }

        $fmt = fn ($v) => $v ? number_format((float) $v, 2, ',', '.') : '?';

        return sprintf('%s × %s × %s', $fmt($this->comprimento), $fmt($this->largura), $fmt($this->altura));
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
