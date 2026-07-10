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
        'local_origem_id',
        'local_destino_id',
        'prazo_atendimento_demanda',
        'data_hora_inicio_carregamento',
        'data_hora_fim_carregamento',
        'data_hora_saida_origem',
        'data_hora_chegada_destino',
        'data_hora_inicio_descarregamento',
        'data_hora_fim_descarregamento',
        'status_demanda',
        'observacao_adicional',
        'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cadastro' => TipoCadastro::class,
            'tipo_demanda' => TipoDemanda::class,
            'status_demanda' => StatusDemanda::class,
            'prazo_atendimento_demanda' => 'datetime',
            'data_hora_inicio_carregamento' => 'datetime',
            'data_hora_fim_carregamento' => 'datetime',
            'data_hora_saida_origem' => 'datetime',
            'data_hora_chegada_destino' => 'datetime',
            'data_hora_inicio_descarregamento' => 'datetime',
            'data_hora_fim_descarregamento' => 'datetime',
        ];
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }

    public function localOrigem(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'local_origem_id');
    }

    public function localDestino(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'local_destino_id');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
