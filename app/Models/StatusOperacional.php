<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusOperacional extends Model
{
    protected $table = 'status_operacionais';

    protected $fillable = ['nome', 'cor', 'status'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }
}
