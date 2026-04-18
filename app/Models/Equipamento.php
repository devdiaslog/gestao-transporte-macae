<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipamento extends Model
{
    use SoftDeletes;

    protected $table = 'equipamentos';

    protected $fillable = [
        'tipo_id',
        'modelo_id',
        'divisao_id',
        'sub_divisao_id',
        'implemento_id',
        'implemento_nome_override',
        'id_elog',
        'prefixo',
        'placa',
        'status',
        'status_operacional',
        'documento_demanda',
        'observacao_operacional',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipamento::class, 'tipo_id');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloEquipamento::class, 'modelo_id');
    }

    public function divisao(): BelongsTo
    {
        return $this->belongsTo(Divisao::class, 'divisao_id');
    }

    public function subDivisao(): BelongsTo
    {
        return $this->belongsTo(SubDivisao::class, 'sub_divisao_id');
    }

    public function implemento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class, 'implemento_id');
    }
}
