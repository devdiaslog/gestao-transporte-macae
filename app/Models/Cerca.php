<?php

namespace App\Models;

use Database\Factories\CercaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cerca extends Model
{
    /** @use HasFactory<CercaFactory> */
    use HasFactory;

    protected $fillable = ['nome', 'atividade', 'poligono', 'status'];

    protected $casts = [
        'poligono' => 'array',
        'status' => 'boolean',
    ];
}
