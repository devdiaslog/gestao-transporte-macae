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
        $demanda->data_hora_fim_demanda = $this->dataFim($itens);

        if ($novoStatus = $this->status($itens, $demanda)) {
            $demanda->status_demanda = $novoStatus;
        }

        $demanda->save();

        return $demanda;
    }

    /**
     * Fim da demanda: definido automaticamente com a maior data/hora de entrega
     * dos itens, mas só quando todos os itens já foram resolvidos (com status).
     * Enquanto houver item pendente, a demanda não tem fim.
     *
     * @param  Collection<int, DemandaItem>  $itens
     */
    public function dataFim(Collection $itens): ?Carbon
    {
        if ($itens->isEmpty() || $itens->contains(fn ($i) => $i->status_item?->encerrado() !== true)) {
            return null;
        }

        $entregas = $itens->pluck('data_hora_entrega')->filter();

        return $entregas->isNotEmpty() ? $entregas->max() : null;
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
     * todos cancelados → Cancelada; todos recusados → Recusa; todos suspensos →
     * Suspensa; todos os itens resolvidos (mesmo misto) → Finalizado; algum
     * resolvido ou demanda já iniciada → Em Andamento; senão Pendente.
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

        $cancelados = $statuses->filter(fn ($s) => $s === StatusItemDemanda::Cancelado)->count();
        $recusados = $statuses->filter(fn ($s) => $s === StatusItemDemanda::Recusado)->count();
        $suspensos = $statuses->filter(fn ($s) => $s === StatusItemDemanda::Suspenso)->count();
        $encerrados = $statuses->filter(fn ($s) => $s->encerrado())->count();
        $total = $statuses->count();

        if ($cancelados === $total) {
            return StatusDemanda::Cancelada;
        }

        if ($recusados === $total) {
            return StatusDemanda::Recusa;
        }

        if ($suspensos === $total) {
            return StatusDemanda::Suspensa;
        }

        // Todos os itens resolvidos (mesmo com mistura de entregues/cancelados/suspensos).
        if ($encerrados === $total) {
            return StatusDemanda::Finalizado;
        }

        // Resolvidos parcialmente, ou execução já iniciada, caracterizam andamento.
        if ($encerrados > 0 || $demanda->data_hora_inicio_demanda !== null) {
            return StatusDemanda::EmAndamento;
        }

        return StatusDemanda::Pendente;
    }
}
