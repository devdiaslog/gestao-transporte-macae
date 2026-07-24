<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandaCapturaElog extends Model
{
    protected $table = 'demanda_captura_elog';

    protected $fillable = [
        'demanda_id',
        'primeira_captura',
        'ultima_captura',
    ];

    protected function casts(): array
    {
        return [
            'primeira_captura' => 'datetime',
            'ultima_captura' => 'datetime',
        ];
    }

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class);
    }
}
