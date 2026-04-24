<?php

namespace App\Models;

use Database\Factories\OcorrenciaFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ocorrencia extends Model
{
    /** @use HasFactory<OcorrenciaFactory> */
    use HasFactory;

    protected $primaryKey = 'id_ocorrencia';

    protected $fillable = [
        'id_veiculo',
        'id_tipo',
        'id_responsavel',
        'id_justificativa',
        'data_hora_inicio',
        'data_hora_fim',
        'observacao',
        'documento',
    ];

    protected function casts(): array
    {
        return [
            'data_hora_inicio' => 'datetime',
            'data_hora_fim' => 'datetime',
        ];
    }

    public function statusOcorrencia(): Attribute
    {
        return Attribute::make(
            get: fn () => is_null($this->data_hora_fim) ? 'Em Aberto' : 'Fechada',
        );
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class, 'id_veiculo');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoOcorrencia::class, 'id_tipo', 'id_tipo');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Responsavel::class, 'id_responsavel', 'id_responsavel');
    }

    public function justificativa(): BelongsTo
    {
        return $this->belongsTo(Justificativa::class, 'id_justificativa', 'id_justificativa');
    }
}
