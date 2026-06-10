<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusEvento extends Model
{
    protected $fillable = [
        'equipamento_id',
        'status_operacional',
        'documento',
        'observacao',
        'entrada_em',
        'saida_em',
        'duracao_minutos',
    ];

    protected $casts = [
        'entrada_em' => 'datetime',
        'saida_em' => 'datetime',
    ];

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }

    public function estaAberto(): bool
    {
        return $this->saida_em === null;
    }
}
