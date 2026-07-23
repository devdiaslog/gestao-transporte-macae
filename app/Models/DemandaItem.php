<?php

namespace App\Models;

use App\Enums\StatusItemDemanda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'prazo_item' => 'datetime',
            'data_hora_entrega' => 'datetime',
            'campos_editados' => 'array',
        ];
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

    /**
     * Prazo ainda alcançável (não vencido) na data corrente.
     */
    public function prazoExequivel(): bool
    {
        return $this->prazo_item !== null && $this->prazo_item->isFuture();
    }
}
