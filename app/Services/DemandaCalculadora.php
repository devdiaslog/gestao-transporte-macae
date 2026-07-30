<?php

namespace App\Services;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\TipoDemanda;
use App\Models\Alerta;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\Equipamento;
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
     * $origem identifica quem provocou o recálculo ('operador' ou 'sap') e
     * define o texto do alerta criado quando a demanda finaliza.
     */
    public function recalcular(Demanda $demanda, string $origem = 'operador'): Demanda
    {
        $itens = $demanda->relationLoaded('itens')
            ? $demanda->itens
            : $demanda->itens()->get();

        $demanda->fonte_demanda = FonteDemanda::fromNumeroDemanda($demanda->numero_demanda);

        // O tipo é derivado dos itens, salvo quando fixado manualmente pelo operador.
        if (! $demanda->tipo_demanda_manual) {
            $demanda->tipo_demanda = $this->tipo($itens);
        }

        $demanda->prazo_demanda = $this->prazo($itens);

        // Início automático: se a torre ainda não iniciou mas o SAP já
        // registrou entregas, assume a mais antiga como início para a demanda
        // não ficar presa em Pendente. Início definido pelo operador prevalece;
        // a flag marca a demanda para o operador conferir o horário (o SAP
        // costuma trazer hora genérica 00:00).
        if ($demanda->data_hora_inicio_demanda === null && ($inicio = $this->dataInicio($itens))) {
            $demanda->data_hora_inicio_demanda = $inicio;
            $demanda->inicio_automatico = true;
        }

        // Fim: gerido automaticamente enquanto o operador não assumir o
        // horário (fim_automatico falso com fim preenchido = fim do operador).
        if ($demanda->fim_automatico || $demanda->data_hora_fim_demanda === null) {
            $novoFim = $this->dataFim($itens);
            $demanda->data_hora_fim_demanda = $novoFim;
            $demanda->fim_automatico = $novoFim !== null;
        }

        // Início igual ao fim (demanda de 1 item, ou todos entregues no mesmo
        // horário genérico do SAP) é duração zero: sinaliza ajuste sempre.
        if ($demanda->data_hora_inicio_demanda !== null
            && $demanda->data_hora_fim_demanda !== null
            && $demanda->data_hora_inicio_demanda->equalTo($demanda->data_hora_fim_demanda)) {
            $demanda->inicio_automatico = true;
            $demanda->fim_automatico = true;
        }

        if ($novoStatus = $this->status($itens, $demanda)) {
            $demanda->status_demanda = $novoStatus;
        }

        $finalizouAgora = $demanda->isDirty('status_demanda')
            && $demanda->status_demanda === StatusDemanda::Finalizado;

        $demanda->save();

        if ($finalizouAgora) {
            $this->alertarFinalizacao($demanda, $origem);
        }

        // Grupo efetivo do veículo = tipo da demanda em andamento mais recente.
        if ($demanda->equipamento_id !== null) {
            $this->sincronizarGrupoVeiculo($demanda->equipamento_id);
        }

        return $demanda;
    }

    /**
     * Persiste no veículo o grupo (tipo) da sua demanda em andamento de início
     * mais recente. Sem demanda em andamento, limpa (cai na subdivisão).
     */
    private function sincronizarGrupoVeiculo(int $equipamentoId): void
    {
        $tipo = Demanda::query()
            ->where('equipamento_id', $equipamentoId)
            ->where('status_demanda', StatusDemanda::EmAndamento)
            ->whereNotNull('data_hora_inicio_demanda')
            ->whereNotNull('tipo_demanda')
            ->orderByDesc('data_hora_inicio_demanda')
            ->value('tipo_demanda');

        $novo = $tipo instanceof TipoDemanda ? $tipo->value : $tipo;

        $equipamento = Equipamento::find($equipamentoId);

        if ($equipamento !== null && $equipamento->grupo_demanda !== $novo) {
            $equipamento->update(['grupo_demanda' => $novo]);
        }
    }

    /**
     * Alerta padrão do sistema quando a demanda finaliza: visível a todos e
     * disparado de imediato no sino, identificando quem finalizou.
     */
    private function alertarFinalizacao(Demanda $demanda, string $origem): void
    {
        $lembrete = $origem === 'sap'
            ? "Demanda #{$demanda->numero_demanda} finalizada via SAP — confira o início e o fim (hora pode estar genérica)."
            : "Demanda #{$demanda->numero_demanda} finalizada pelo operador.";

        Alerta::create([
            'equipamento_id' => $demanda->equipamento_id,
            'criado_por' => auth()->id(),
            'lembrete' => $lembrete,
            'tipo' => 'demanda',
            'data_hora_alerta' => now(),
            'condicao' => 'demanda_finalizada_'.$origem,
            'para_todos' => true,
        ]);
    }

    /**
     * Início sugerido pelas entregas do SAP: a data/hora de entrega mais antiga
     * entre os itens. Usado apenas quando o operador não definiu o início.
     *
     * @param  Collection<int, DemandaItem>  $itens
     */
    public function dataInicio(Collection $itens): ?Carbon
    {
        $entregas = $itens->pluck('data_hora_entrega')->filter();

        return $entregas->isNotEmpty() ? $entregas->min() : null;
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
