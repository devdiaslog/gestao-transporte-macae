<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrador = 'administrador';
    case Supervisor = 'supervisor';
    case Operador = 'operador';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Supervisor => 'Supervisor',
            self::Operador => 'Operador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Administrador => 'violet',
            self::Supervisor => 'blue',
            self::Operador => 'zinc',
        };
    }
}
