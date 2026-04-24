<?php

namespace App\Models;

use Database\Factories\ContatoMotoristaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContatoMotorista extends Model
{
    /** @use HasFactory<ContatoMotoristaFactory> */
    use HasFactory;

    protected $fillable = [
        'motorista_id',
        'telefone',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }
}
