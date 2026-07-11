<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Local extends Model
{
    protected $table = 'locais';

    protected $fillable = ['nome', 'ativo', 'precisa_agendamento'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'precisa_agendamento' => 'boolean',
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
