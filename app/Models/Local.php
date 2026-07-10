<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Local extends Model
{
    protected $fillable = ['nome', 'ativo'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function demandasOrigem(): HasMany
    {
        return $this->hasMany(Demanda::class, 'local_origem_id');
    }

    public function demandasDestino(): HasMany
    {
        return $this->hasMany(Demanda::class, 'local_destino_id');
    }

    public function scopeAtivo($query): void
    {
        $query->where('ativo', true);
    }
}
