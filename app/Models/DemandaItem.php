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
    ];

    protected function casts(): array
    {
        return [
            'status_item' => StatusItemDemanda::class,
            'prazo_item' => 'datetime',
            'data_hora_entrega' => 'datetime',
        ];
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
