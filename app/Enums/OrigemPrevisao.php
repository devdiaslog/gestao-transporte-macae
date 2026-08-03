<?php

namespace App\Enums;

/**
 * Como a previsão de entrega de um item foi definida.
 */
enum OrigemPrevisao: string
{
    case Manual = 'manual';
    case Lote = 'lote';
    case Automacao = 'automacao';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Lote => 'Em lote',
            self::Automacao => 'Automação',
        };
    }
}
