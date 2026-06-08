<?php

namespace App\Models;

use App\Enums\AtividadeCerca;
use App\Traits\HasEncryptedRouteKey;
use Database\Factories\CercaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cerca extends Model
{
    /** @use HasFactory<CercaFactory> */
    use HasEncryptedRouteKey, HasFactory;

    protected $fillable = ['nome', 'atividade', 'poligono', 'status', 'tempo_minimo', 'tempo_maximo'];

    protected $casts = [
        'atividade' => AtividadeCerca::class,
        'poligono' => 'array',
        'status' => 'boolean',
    ];
}
