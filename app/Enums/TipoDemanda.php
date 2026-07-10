<?php

namespace App\Enums;

enum TipoDemanda: string
{
    case Load = 'load';
    case Backload = 'backload';
    case Transferencia = 'transferencia';

    public function label(): string
    {
        return match ($this) {
            self::Load => 'Load',
            self::Backload => 'Backload',
            self::Transferencia => 'Transferência',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Load => 'blue',
            self::Backload => 'amber',
            self::Transferencia => 'violet',
        };
    }
}
