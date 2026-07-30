<?php

namespace App\Models;

use App\Traits\HasEncryptedRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Equipamento extends Model
{
    use HasEncryptedRouteKey, SoftDeletes;

    protected $table = 'equipamentos';

    protected $fillable = [
        'tipo_id',
        'modelo_id',
        'divisao_id',
        'sub_divisao_id',
        'implemento_id',
        'implemento_nome_override',
        'motorista_id',
        'id_elog',
        'id_rastreador',
        'prefixo',
        'placa',
        'status',
        'status_operacional',
        'documento_demanda',
        'observacao_operacional',
        'origem',
        'destino',
        'grupo_demanda',
    ];

    /**
     * Rótulos dos grupos efetivos, na ordem de exibição.
     *
     * @var array<string, string>
     */
    public const GRUPOS = [
        'load' => 'Load',
        'backload' => 'Backload',
        'transferencia' => 'Transferência',
        'sem_grupo' => 'Sem grupo',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    /**
     * Grupo efetivo do veículo: o tipo da última demanda em andamento
     * (grupo_demanda) ou, na falta, o grupo derivado da subdivisão.
     * Retorna a chave canônica: load | backload | transferencia | sem_grupo.
     */
    public function grupoEfetivo(): string
    {
        if ($this->grupo_demanda !== null && $this->grupo_demanda !== '') {
            return $this->grupo_demanda;
        }

        return self::grupoDaSubdivisao($this->subDivisao?->nome);
    }

    /**
     * Mapeia o nome da subdivisão para um grupo, por prioridade:
     * transferência → load → backload → sem grupo.
     */
    public static function grupoDaSubdivisao(?string $nome): string
    {
        $n = mb_strtolower(Str::ascii((string) $nome));

        return match (true) {
            str_contains($n, 'transferencia') => 'transferencia',
            str_contains($n, 'load') => 'load',
            str_contains($n, 'backload') => 'backload',
            default => 'sem_grupo',
        };
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipamento::class, 'tipo_id');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloEquipamento::class, 'modelo_id');
    }

    public function divisao(): BelongsTo
    {
        return $this->belongsTo(Divisao::class, 'divisao_id');
    }

    public function subDivisao(): BelongsTo
    {
        return $this->belongsTo(SubDivisao::class, 'sub_divisao_id');
    }

    public function implemento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class, 'implemento_id');
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class, 'motorista_id');
    }

    public function posicao(): HasOne
    {
        return $this->hasOne(PosicaoVeiculo::class, 'license_plate', 'id_rastreador');
    }

    public function ultimoLogOperacional(): HasOne
    {
        return $this->hasOne(EquipamentoLog::class)
            ->whereIn('campo', ['Status Operacional', 'Documento de Demanda'])
            ->latest();
    }
}
