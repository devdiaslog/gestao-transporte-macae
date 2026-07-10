<?php

namespace App\Enums;

enum TipoCadastro: string
{
    case Manual = 'manual';
    case Integracao = 'integracao';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Integracao => 'Integração',
        };
    }
}
