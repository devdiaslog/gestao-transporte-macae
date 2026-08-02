<?php

namespace App\Models;

use App\Enums\OrigemPrevisao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma promessa de entrega feita ao cliente.
 *
 * O item guarda apenas a previsão vigente; cada remarcação vira uma linha aqui,
 * preservando quem prometeu o quê e quando.
 */
class DemandaItemPrevisao extends Model
{
    protected $table = 'demanda_item_previsoes';

    protected $fillable = [
        'demanda_item_id',
        'data_hora_previsao',
        'origem',
        'definido_por',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'data_hora_previsao' => 'datetime',
            'origem' => OrigemPrevisao::class,
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DemandaItem::class, 'demanda_item_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'definido_por');
    }

    /**
     * Nome de quem definiu a previsão, ou o rótulo da origem quando não houve
     * usuário por trás (automação).
     */
    public function autorLabel(): string
    {
        return $this->autor?->name ?? $this->origem->label();
    }
}
