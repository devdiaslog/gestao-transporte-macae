<?php

namespace App\Models;

use App\Traits\HasEncryptedRouteKey;
use Database\Factories\JustificativaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Justificativa extends Model
{
    /** @use HasFactory<JustificativaFactory> */
    use HasEncryptedRouteKey, HasFactory;

    protected $primaryKey = 'id_justificativa';

    protected $fillable = [
        'descricao',
        'ativo',
        'obrigar_observacao',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'obrigar_observacao' => 'boolean',
        ];
    }

    public function tiposOcorrencia(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoOcorrencia::class,
            'tipo_justificativa',
            'id_justificativa',
            'id_tipo'
        );
    }
}
