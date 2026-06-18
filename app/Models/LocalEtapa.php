<?php

namespace App\Models;

use App\Traits\HasEncryptedRouteKey;
use Database\Factories\LocalEtapaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalEtapa extends Model
{
    /** @use HasFactory<LocalEtapaFactory> */
    use HasEncryptedRouteKey, HasFactory;

    protected $fillable = [
        'nome',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(Etapa::class);
    }
}
