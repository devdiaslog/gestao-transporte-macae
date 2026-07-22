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
        'status_item',
        'prazo_item',
        'data_hora_entrega',
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
