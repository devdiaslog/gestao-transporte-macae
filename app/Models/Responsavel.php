<?php

namespace App\Models;

use App\Enums\TipoResponsavel;
use App\Traits\HasEncryptedRouteKey;
use Database\Factories\ResponsavelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Responsavel extends Model
{
    /** @use HasFactory<ResponsavelFactory> */
    use HasEncryptedRouteKey, HasFactory;

    protected $table = 'responsaveis';

    protected $primaryKey = 'id_responsavel';

    protected $fillable = [
        'nome',
        'tipo',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoResponsavel::class,
            'ativo' => 'boolean',
        ];
    }

    public function tipos(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoOcorrencia::class,
            'responsavel_tipo_ocorrencia',
            'id_responsavel',
            'id_tipo'
        );
    }
}
