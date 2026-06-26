<?php

namespace App\Models;

use App\Traits\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    use HasEncryptedRouteKey;

    protected $table = 'alertas';

    protected $fillable = [
        'equipamento_id',
        'criado_por',
        'lembrete',
        'tipo',
        'data_hora_alerta',
        'condicao',
        'para_todos',
        'status',
        'resolvido_por',
        'resolvido_em',
    ];

    protected function casts(): array
    {
        return [
            'data_hora_alerta' => 'datetime',
            'resolvido_em' => 'datetime',
            'para_todos' => 'boolean',
        ];
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function resolvedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolvido_por');
    }

    /** Alertas visíveis ao usuário: os marcados para todos ou os criados por ele. */
    public function scopeVisivelPara(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('para_todos', true)->orWhere('criado_por', $userId));
    }

    /** Já passou da hora e ainda está pendente. */
    public function getDisparadoAttribute(): bool
    {
        return $this->status === 'pendente'
            && $this->data_hora_alerta !== null
            && $this->data_hora_alerta->isPast();
    }
}
