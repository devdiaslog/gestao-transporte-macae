<?php

namespace App\Models;

use App\Traits\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Model;

class Medicao extends Model
{
    use HasEncryptedRouteKey;

    protected $table = 'medicoes';

    protected $fillable = [
        'nome_medicao',
        'data_inicio',
        'data_fim',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }
}
