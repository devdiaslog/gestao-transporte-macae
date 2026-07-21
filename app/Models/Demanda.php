<?php

namespace App\Models;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use App\Enums\TipoCadastro;
use App\Enums\TipoDemanda;
use Database\Factories\DemandaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Demanda extends Model
{
    /** @use HasFactory<DemandaFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_demanda',
        'tipo_cadastro',
        'fonte_demanda',
        'tipo_demanda',
        'equipamento_id',
        'documento_demanda',
        'prazo_demanda',
        'data_hora_inicio_demanda',
        'data_hora_fim_demanda',
        'status_demanda',
        'status_auditoria',
        'observacao',
        'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cadastro' => TipoCadastro::class,
            'fonte_demanda' => FonteDemanda::class,
            'tipo_demanda' => TipoDemanda::class,
            'status_demanda' => StatusDemanda::class,
            'prazo_demanda' => 'datetime',
            'data_hora_inicio_demanda' => 'datetime',
            'data_hora_fim_demanda' => 'datetime',
            'status_auditoria' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Demanda $demanda): void {
            if (
                $demanda->status_demanda === StatusDemanda::Pendente
                && $demanda->data_hora_inicio_demanda !== null
                && $demanda->equipamento_id !== null
            ) {
                $demanda->status_demanda = StatusDemanda::EmAndamento;
            }
        });
    }

    public function itens(): HasMany
    {
        return $this->hasMany(DemandaItem::class);
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class);
    }

    /**
     * Locais de origem distintos dos itens, na ordem em que aparecem.
     *
     * @return array<int, string>
     */
    public function locaisOrigem(): array
    {
        return $this->itens->pluck('local_origem')->filter()->unique()->values()->all();
    }

    /**
     * Locais de destino distintos dos itens, na ordem em que aparecem.
     *
     * @return array<int, string>
     */
    public function locaisDestino(): array
    {
        return $this->itens->pluck('local_destino')->filter()->unique()->values()->all();
    }

    /**
     * Rota consolidada da demanda: todas as origens → todos os destinos.
     */
    public function rota(): string
    {
        $origens = $this->locaisOrigem();
        $destinos = $this->locaisDestino();

        if ($origens === [] && $destinos === []) {
            return '';
        }

        return implode(', ', $origens).' → '.implode(', ', $destinos);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
