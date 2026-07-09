<?php

namespace App\Enums;

enum UserPermission: string
{
    case Dashboard = 'dashboard';

    public function label(): string
    {
        return match ($this) {
            self::Dashboard => 'Dashboard de Transporte',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Dashboard => 'Acesso ao painel de indicadores e gráficos da operação.',
        };
    }
}
