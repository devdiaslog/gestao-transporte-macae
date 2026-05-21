<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reporte extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_reporte',
        'data_hora_emissao',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'data_hora_emissao' => 'datetime',
        ];
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ReporteItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
