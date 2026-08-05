<?php

namespace App\Enums;

/**
 * Qual prazo do trecho vale por padrão para o item que o percorre.
 */
enum PrazoPadrao: string
{
    case Normal = 'normal';
    case Expresso = 'expresso';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Expresso => 'Expresso',
            self::Manual => 'Manual',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'zinc',
            self::Expresso => 'amber',
            self::Manual => 'sky',
        };
    }
}
