<?php

namespace App\Services;

use App\Models\DemandaItem;
use App\Models\TrechoSap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aplica aos itens o prazo acordado da rota que eles percorrem.
 *
 * A contagem começa na liberação da RT: é quando o cliente libera o material
 * e o relógio passa a correr para nós.
 *
 * O cálculo é uma ação da equipe, não um efeito da importação. A tabela de
 * trechos é mantida em lote e pode mudar depois de os itens já existirem —
 * recalcular na hora errada trocaria prazos sem ninguém pedir.
 */
class CalculadoraPrazoTrecho
{
    /**
     * Motivos que impedem o cálculo, na ordem em que são verificados.
     */
    public const SEM_ROTA = 'sem_rota';

    public const SEM_TRECHO = 'sem_trecho';

    public const SEM_LIBERACAO = 'sem_liberacao';

    public const SEM_PRAZO_NO_TRECHO = 'sem_prazo_no_trecho';

    public const RENEGOCIADO = 'renegociado';

    /**
     * Recalcula o prazo dos itens informados.
     *
     * Prazo renegociado com o cliente é decisão da operação e não é desfeito
     * aqui: o item é contado como pulado para a tela poder avisar.
     *
     * @param  Collection<int, DemandaItem>  $itens
     * @return array{aplicados: int, pulados: array<string, int>}
     */
    public function recalcular(Collection $itens, ?int $usuarioId = null): array
    {
        $trechos = TrechoSap::all()->keyBy('chave_origem_destino');

        $resultado = ['aplicados' => 0, 'pulados' => []];

        foreach ($itens as $item) {
            $motivo = $this->motivoParaNaoCalcular($item, $trechos);

            if ($motivo !== null) {
                $resultado['pulados'][$motivo] = ($resultado['pulados'][$motivo] ?? 0) + 1;

                continue;
            }

            $trecho = $trechos->get($this->chaveDe($item));

            $item->forceFill([
                'prazo_item' => $item->data_hora_liberacao_rt->copy()->addHours($trecho->horasVigentes()),
                'prazo_trecho_id' => $trecho->id,
                'prazo_calculado_em' => now(),
                'prazo_alterado_por' => $usuarioId,
            ])->save();

            $resultado['aplicados']++;
        }

        return $resultado;
    }

    /**
     * Itens que ainda esperam o prazo da rota.
     *
     * São os que nunca foram calculados ou cujo trecho mudou depois do último
     * cálculo — o prazo cadastrado é corrigido com frequência, e o item não
     * pode ficar preso a um valor que a operação já revisou.
     *
     * @return Builder<DemandaItem>
     */
    public static function pendentes(): Builder
    {
        return DemandaItem::query()
            ->where('fora_escopo', false)
            ->where(fn ($q) => $q
                ->whereNull('prazo_calculado_em')
                ->orWhereHas('trechoDoPrazo', fn ($t) => $t->whereColumn('trechos_sap.updated_at', '>', 'demanda_itens.prazo_calculado_em')));
    }

    /**
     * Por que este item não pode ter o prazo calculado, ou null se pode.
     *
     * @param  Collection<string, TrechoSap>  $trechos
     */
    public function motivoParaNaoCalcular(DemandaItem $item, Collection $trechos): ?string
    {
        // "Assumiu o campo" é o critério, não "difere do SAP": item renegociado
        // que nunca teve prazo do SAP também é decisão da equipe e não pode ser
        // desfeito por um recálculo.
        if ($item->campoEditadoPeloOperador('prazo_item')) {
            return self::RENEGOCIADO;
        }

        if (blank($item->local_origem_norm) || blank($item->local_destino_norm)) {
            return self::SEM_ROTA;
        }

        $trecho = $trechos->get($this->chaveDe($item));

        if ($trecho === null) {
            return self::SEM_TRECHO;
        }

        if ($trecho->horasVigentes() === null) {
            return self::SEM_PRAZO_NO_TRECHO;
        }

        if ($item->data_hora_liberacao_rt === null) {
            return self::SEM_LIBERACAO;
        }

        return null;
    }

    /**
     * Rótulo do motivo, para a tela dizer o que falta.
     */
    public static function rotuloDoMotivo(string $motivo): string
    {
        return match ($motivo) {
            self::SEM_ROTA => 'sem origem ou destino',
            self::SEM_TRECHO => 'rota não cadastrada em Trechos SAP',
            self::SEM_LIBERACAO => 'sem data de liberação da RT',
            self::SEM_PRAZO_NO_TRECHO => 'trecho cadastrado sem prazo',
            self::RENEGOCIADO => 'prazo renegociado pela equipe',
            default => $motivo,
        };
    }

    private function chaveDe(DemandaItem $item): string
    {
        return $item->local_origem_norm.' > '.$item->local_destino_norm;
    }
}
