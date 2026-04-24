<?php

namespace App\Models;

use Database\Factories\MotoristaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Motorista extends Model
{
    /** @use HasFactory<MotoristaFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'matricula',
        'nome',
        'cpf',
        'cargo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function contatos(): HasMany
    {
        return $this->hasMany(ContatoMotorista::class);
    }
}
