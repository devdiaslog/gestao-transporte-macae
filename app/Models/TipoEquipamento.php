<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEquipamento extends Model
{
    use SoftDeletes;

    protected $table = 'tipos_equipamentos';

    protected $fillable = [
        'nome',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function modelosEquipamentos(): HasMany
    {
        return $this->hasMany(ModeloEquipamento::class, 'tipo_equipamento_id');
    }
}
