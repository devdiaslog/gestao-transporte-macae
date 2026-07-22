<?php

namespace App\Enums;

enum StatusDemanda: string
{
    case Pendente = 'pendente';
    case EmAndamento = 'em_andamento';
    case Finalizado = 'finalizado';
    case Cancelada = 'cancelada';
    case Recusa = 'recusa';
    case Suspensa = 'suspensa';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::EmAndamento => 'Em Andamento',
            self::Finalizado => 'Finalizado',
            self::Cancelada => 'Cancelada',
            self::Recusa => 'Recusa',
            self::Suspensa => 'Suspensa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente => 'zinc',
            self::EmAndamento => 'blue',
            self::Finalizado => 'emerald',
            self::Cancelada => 'rose',
            self::Recusa => 'orange',
            self::Suspensa => 'amber',
        };
    }
}
