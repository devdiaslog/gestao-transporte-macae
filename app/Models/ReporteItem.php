<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporte_id',
        'prefixo',
        'status_operacional',
        'tempo_parado',
        'observacao',
    ];

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class);
    }
}
