<?php

namespace App\Models;

use App\Traits\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubDivisao extends Model
{
    use HasEncryptedRouteKey, SoftDeletes;

    protected $table = 'sub_divisoes';

    protected $fillable = [
        'divisao_id',
        'nome',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function divisao(): BelongsTo
    {
        return $this->belongsTo(Divisao::class);
    }
}
