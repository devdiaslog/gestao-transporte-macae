<?php

namespace App\Enums;

enum StatusAuditoria: string
{
    case Pendente = 'pendente';
    case Aprovada = 'aprovada';
    case Reprovada = 'reprovada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovada => 'Aprovada',
            self::Reprovada => 'Reprovada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente => 'amber',
            self::Aprovada => 'emerald',
            self::Reprovada => 'red',
        };
    }
}
