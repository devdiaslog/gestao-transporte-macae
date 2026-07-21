<?php

namespace App\Enums;

/**
 * Códigos de status do item vindos do SAP.
 *
 * O código 07 foi confirmado como "entregue": no export de referência todas as
 * 96 linhas com status 07 possuíam data de entrega preenchida, e nenhuma das
 * demais possuía. Os códigos 04 e 18 foram inferidos pela ausência de data de
 * entrega e pela frequência (04 é o estado corrente da maioria dos itens).
 */
enum StatusItemDemanda: string
{
    case Aberto = '04';
    case Entregue = '07';
    case Cancelado = '18';

    public function label(): string
    {
        return match ($this) {
            self::Aberto => 'Aberto',
            self::Entregue => 'Entregue',
            self::Cancelado => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Aberto => 'zinc',
            self::Entregue => 'emerald',
            self::Cancelado => 'rose',
        };
    }

    /**
     * Item que já teve seu ciclo encerrado (entregue ou cancelado).
     */
    public function encerrado(): bool
    {
        return $this !== self::Aberto;
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
