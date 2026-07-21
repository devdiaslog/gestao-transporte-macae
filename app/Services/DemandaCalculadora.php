<?php

namespace App\Services;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\TipoDemanda;
use App\Models\Demanda;
use App\Models\DemandaItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Recalcula na demanda os campos derivados dos seus itens.
 */
class DemandaCalculadora
{
    /**
     * Pontos-chave que definem Load e Backload.
     */
    public const PONTOS_CHAVE = ['BMAC', 'PACU', 'PBG'];

    /**
     * Recalcula fonte, tipo, prazo e status a partir dos itens e persiste.
     */
    public function recalcular(Demanda $demanda): Demanda
    {
        $itens = $demanda->relationLoaded('itens')
            ? $demanda->itens
            : $demanda->itens()->get();

        $demanda->fonte_demanda = FonteDemanda::fromNumeroDemanda($demanda->numero_demanda);
        $demanda->tipo_demanda = $this->tipo($itens);
        $demanda->prazo_demanda = $this->prazo($itens);

        if ($novoStatus = $this->status($itens, $demanda)) {
            $demanda->status_demanda = $novoStatus;
        }

        $demanda->save();

        return $demanda;
    }

    /**
     * Load quando algum destino é ponto-chave; Backload quando alguma origem é;
     * caso contrário Transferência. Sem itens, o tipo fica indefinido.
     *
     * @param  Collection<int, DemandaItem>  $itens
     */
    public function tipo(Collection $itens): ?TipoDemanda
    {
        if ($itens->isEmpty()) {
            return null;
        }

        $normaliza = fn (?string $v): string => strtoupper(trim((string) $v));

        $destinos = $itens->pluck('local_destino')->map($normaliza)->filter();
        $origens = $itens->pluck('local_origem')->map($normaliza)->filter();

        if ($destinos->intersect(self::PONTOS_CHAVE)->isNotEmpty()) {
            return TipoDemanda::Load;
        }

        if ($origens->intersect(self::PONTOS_CHAVE)->isNotEmpty()) {
            return TipoDemanda::Backload;
        }

        return TipoDemanda::Transferencia;
    }

    /**
     * Menor prazo ainda exequível entre os itens em aberto. Se nenhum for
     * alcançável, usa o menor prazo entre eles (o mais antigo vencido).
     *
     * @param  Collection<int, DemandaItem>  $itens
     */
    public function prazo(Collection $itens): ?Carbon
    {
        $prazos = $itens
            ->reject(fn ($item) => $item->status_item?->encerrado() === true)
            ->pluck('prazo_item')
            ->filter();

        // Sem itens em aberto, considera o conjunto completo para não perder a referência.
        if ($prazos->isEmpty()) {
            $prazos = $itens->pluck('prazo_item')->filter();
        }

        if ($prazos->isEmpty()) {
            return null;
        }

        $agora = now();
        $exequiveis = $prazos->filter(fn ($p) => $p->isAfter($agora));

        return $exequiveis->isNotEmpty() ? $exequiveis->min() : $prazos->min();
    }

    /**
     * Status da demanda derivado dos itens:
     * todos entregues → Finalizado; todos cancelados → Cancelada;
     * algum encerrado ou demanda já iniciada → Em Andamento; senão Pendente.
     *
     * @param  Collection<int, DemandaItem>  $itens
     */
    public function status(Collection $itens, Demanda $demanda): ?StatusDemanda
    {
        if ($itens->isEmpty()) {
            return null;
        }

        $statuses = $itens->pluck('status_item')->filter();

        if ($statuses->isEmpty()) {
            return null;
        }

        $entregues = $statuses->filter(fn ($s) => $s === StatusItemDemanda::Entregue)->count();
        $cancelados = $statuses->filter(fn ($s) => $s === StatusItemDemanda::Cancelado)->count();
        $total = $statuses->count();

        if ($entregues === $total) {
            return StatusDemanda::Finalizado;
        }

        if ($cancelados === $total) {
            return StatusDemanda::Cancelada;
        }

        // Encerrados parcialmente, ou execução já iniciada, caracterizam andamento.
        if ($entregues + $cancelados > 0 || $demanda->data_hora_inicio_demanda !== null) {
            return StatusDemanda::EmAndamento;
        }

        return StatusDemanda::Pendente;
    }
}
