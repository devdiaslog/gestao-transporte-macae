<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModeloEquipamento extends Model
{
    use SoftDeletes;

    protected $table = 'modelos_equipamentos';

    protected $fillable = [
        'tipo_equipamento_id',
        'nome',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function tipoEquipamento(): BelongsTo
    {
        return $this->belongsTo(TipoEquipamento::class, 'tipo_equipamento_id');
    }
}
