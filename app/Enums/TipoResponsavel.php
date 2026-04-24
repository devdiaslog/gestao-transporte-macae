<?php

namespace App\Enums;

enum TipoResponsavel: string
{
    case Interno = 'INTERNO';
    case Externo = 'EXTERNO';

    public function label(): string
    {
        return match ($this) {
            self::Interno => 'Interno',
            self::Externo => 'Externo',
        };
    }
}
