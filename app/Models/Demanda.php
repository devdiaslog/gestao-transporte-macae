<?php

namespace App\Models;

use App\Enums\StatusDemanda;
use App\Enums\TipoCadastro;
use App\Enums\TipoDemanda;
use Database\Factories\DemandaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Demanda extends Model
{
    /** @use HasFactory<DemandaFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_demanda',
        'tipo_cadastro',
        'tipo_demanda',
        'equipamento_id',
        'documento_demanda',
        'origem',
        'destino',
        'prazo_referencia',
        'data_hora_inicio_demanda',
        'data_hora_fim_demanda',
        'status_demanda',
        'status_auditoria',
        'observacao',
        'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cadastro' => TipoCadastro::class,
            'tipo_demanda' => TipoDemanda::class,
            'status_demanda' => StatusDemanda::class,
            'prazo_referencia' => 'datetime',
            'data_hora_inicio_demanda' => 'datetime',
            'data_hora_fim_demanda' => 'datetime',
            'status_auditoria' => 'boolean',
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
}
