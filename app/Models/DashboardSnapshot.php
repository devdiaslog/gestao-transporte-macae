<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardSnapshot extends Model
{
    protected $fillable = ['capturado_em', 'dados'];

    protected function casts(): array
    {
        return [
            'capturado_em' => 'datetime',
            'dados' => 'array',
        ];
    }
}
