<?php

namespace App\Enums;

/**
 * Status do item de demanda.
 *
 * O valor interno é canônico (não é o código do SAP), porque um mesmo status
 * pode vir de mais de um código — Suspenso corresponde tanto a 13 quanto a 18.
 * A tradução do código bruto do SAP é feita em {@see self::fromCodigo()}.
 *
 * Quando a distinção entre os códigos importar (13 é responsabilidade nossa,
 * 18 é do cliente), use {@see StatusSap}, que preserva o código original.
 */
enum StatusItemDemanda: string
{
    case Pendente = 'pendente';
    case Entregue = 'entregue';
    case Cancelado = 'cancelado';
    case Suspenso = 'suspenso';
    case Recusado = 'recusado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Entregue => 'Entregue',
            self::Cancelado => 'Cancelado',
            self::Suspenso => 'Suspenso',
            self::Recusado => 'Recusado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente => 'zinc',
            self::Entregue => 'emerald',
            self::Cancelado => 'rose',
            self::Suspenso => 'amber',
            self::Recusado => 'orange',
        };
    }

    /**
     * Item que já teve seu ciclo encerrado (qualquer status diferente de Pendente).
     * Entregue, Cancelado, Suspenso e Recusado contam como resolvidos.
     */
    public function encerrado(): bool
    {
        return $this !== self::Pendente;
    }

    /**
     * Traduz o código bruto do SAP para o status interno.
     * Tolera valores de 1 dígito ("4" vira "04") e códigos desconhecidos (null).
     */
    public static function fromCodigo(string|int|null $codigo): ?self
    {
        if ($codigo === null || $codigo === '') {
            return null;
        }

        return match (str_pad((string) $codigo, 2, '0', STR_PAD_LEFT)) {
            // 03 (liberado pelo cliente) e 04 (programado) são itens que ainda
            // aguardam entrega — do ponto de vista operacional, ambos pendentes.
            '03', '04' => self::Pendente,
            '07' => self::Entregue,
            '09' => self::Cancelado,
            '13', '18' => self::Suspenso,
            default => null,
        };
    }
}
