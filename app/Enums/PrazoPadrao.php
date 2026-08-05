<?php

namespace App\Enums;

/**
 * Qual prazo do trecho vale por padrão para o item que o percorre.
 */
enum PrazoPadrao: string
{
    case Normal = 'normal';
    case Expresso = 'expresso';
    case Automatico = 'automatico';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Expresso => 'Expresso',
            self::Automatico => 'Automático',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Normal => 'zinc',
            self::Expresso => 'amber',
            self::Automatico => 'sky',
        };
    }
}
