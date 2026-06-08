<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CercaEvento extends Model
{
    protected $fillable = [
        'cerca_id',
        'equipamento_id',
        'entrada_em',
        'saida_em',
        'duracao_minutos',
        'excedeu_maximo',
    ];

    protected $casts = [
        'entrada_em' => 'datetime',
        'saida_em' => 'datetime',
        'excedeu_maximo' => 'boolean',
    ];

    public function cerca(): BelongsTo
    {
        return $this->belongsTo(Cerca::class);
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }

    /** Retorna true se o evento ainda está em aberto (veículo dentro da cerca). */
    public function estaAberto(): bool
    {
        return $this->saida_em === null;
    }
}
