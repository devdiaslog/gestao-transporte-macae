<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteItem extends Model
{
    use HasFactory;

    protected $table = 'reporte_itens';

    protected $fillable = [
        'reporte_id',
        'prefixo',
        'documento',
        'status_operacional',
        'tempo_parado',
        'data_hora_previsao',
        'primeiro_contato',
        'segundo_contato',
        'observacao',
    ];

    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class);
    }
}
