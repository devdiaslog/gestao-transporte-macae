<?php

namespace App\Models;

use App\Traits\HasEncryptedRouteKey;
use Database\Factories\EtapaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Etapa extends Model
{
    /** @use HasFactory<EtapaFactory> */
    use HasEncryptedRouteKey, HasFactory;

    protected $fillable = [
        'equipamento_id',
        'tipo_etapa_id',
        'local_etapa_id',
        'motorista_id',
        'documento',
        'data_hora_inicio',
        'data_hora_fim',
        'observacao',
        'motivo_longa_duracao',
        'emitido_por',
        'finalizado_por',
        'auditado_por',
        'auditado_em',
    ];

    protected function casts(): array
    {
        return [
            'data_hora_inicio' => 'datetime',
            'data_hora_fim' => 'datetime',
            'auditado_em' => 'datetime',
        ];
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }

    public function tipoEtapa(): BelongsTo
    {
        return $this->belongsTo(TipoEtapa::class);
    }

    public function localEtapa(): BelongsTo
    {
        return $this->belongsTo(LocalEtapa::class);
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }

    public function emissor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emitido_por');
    }

    public function finalizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalizado_por');
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditado_por');
    }

    public function getStatusAttribute(): string
    {
        return $this->data_hora_fim ? 'Finalizado' : 'Em Aberto';
    }
}
