<?php

namespace App\Enums;

/**
 * Status do item de demanda.
 *
 * Os códigos 04, 07 e 18 vêm do SAP. O 07 foi confirmado como "entregue": no
 * export de referência todas as 96 linhas com status 07 possuíam data de
 * entrega preenchida, e nenhuma das demais possuía. O 04 é o estado corrente
 * da maioria dos itens (Pendente) e o 18 representa cancelamento.
 *
 * "Recusado" é um estado atribuído manualmente na edição do item (não há
 * código SAP correspondente); quando todos os itens são recusados, a demanda
 * passa a ter status Recusa.
 */
enum StatusItemDemanda: string
{
    case Pendente = '04';
    case Entregue = '07';
    case Cancelado = '18';
    case Recusado = 'recusado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Entregue => 'Entregue',
            self::Cancelado => 'Cancelado',
            self::Recusado => 'Recusado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente => 'zinc',
            self::Entregue => 'emerald',
            self::Cancelado => 'rose',
            self::Recusado => 'orange',
        };
    }

    /**
     * Item que já teve seu ciclo encerrado (entregue, cancelado ou recusado).
     */
    public function encerrado(): bool
    {
        return $this !== self::Pendente;
    }

    /**
     * Resolve o código bruto do SAP, tolerando valores desconhecidos.
     * Aceita tanto "4" quanto "04".
     */
    public static function fromCodigo(string|int|null $codigo): ?self
    {
        if ($codigo === null || $codigo === '') {
            return null;
        }

        return self::tryFrom(str_pad((string) $codigo, 2, '0', STR_PAD_LEFT));
    }
}
