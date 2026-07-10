<?php

namespace App\Enums;

enum StatusDemanda: string
{
    case Pendente = 'pendente';
    case EmAndamento = 'em_andamento';
    case Concluida = 'concluida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::EmAndamento => 'Em Andamento',
            self::Concluida => 'Concluída',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendente => 'zinc',
            self::EmAndamento => 'blue',
            self::Concluida => 'emerald',
            self::Cancelada => 'rose',
        };
    }
}
