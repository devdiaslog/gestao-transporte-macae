<?php

namespace App\Models;

use Database\Factories\TipoOcorrenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoOcorrencia extends Model
{
    /** @use HasFactory<TipoOcorrenciaFactory> */
    use HasFactory;

    protected $table = 'tipos_ocorrencia';

    protected $primaryKey = 'id_tipo';

    protected $fillable = [
        'descricao',
    ];

    public function justificativas(): BelongsToMany
    {
        return $this->belongsToMany(
            Justificativa::class,
            'tipo_justificativa',
            'id_tipo',
            'id_justificativa'
        );
    }

    public function responsaveis(): BelongsToMany
    {
        return $this->belongsToMany(
            Responsavel::class,
            'responsavel_tipo_ocorrencia',
            'id_tipo',
            'id_responsavel'
        );
    }
}
