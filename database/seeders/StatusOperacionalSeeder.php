<?php

namespace Database\Seeders;

use App\Models\StatusOperacional;
use Illuminate\Database\Seeder;

class StatusOperacionalSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['nome' => 'Em Trânsito',          'cor' => '#3B82F6'], // blue-500
            ['nome' => 'Ag-Programação',        'cor' => '#06B6D4'], // cyan-500
            ['nome' => 'Ag-Descarregamento',    'cor' => '#F97316'], // orange-500
            ['nome' => 'Em Operação Interna',   'cor' => '#8B5CF6'], // violet-500
            ['nome' => 'Recusa',                'cor' => '#F43F5E'], // rose-500
            ['nome' => 'Ag-Carregamento',       'cor' => '#F59E0B'], // amber-500
            ['nome' => 'Carregado',             'cor' => '#10B981'], // emerald-500
            ['nome' => 'Manutenção',            'cor' => '#EF4444'], // red-500
            ['nome' => 'Ag-Motorista',          'cor' => '#84CC16'], // lime-500
            ['nome' => 'Ag-Documentação',       'cor' => '#EAB308'], // yellow-500
            ['nome' => 'Frota Reserva',         'cor' => '#71717A'], // zinc-500
            ['nome' => 'Reservado',             'cor' => '#6366F1'], // indigo-500
        ];

        foreach ($statuses as $data) {
            StatusOperacional::updateOrCreate(
                ['nome' => $data['nome']],
                ['cor' => $data['cor'], 'status' => true],
            );
        }
    }
}
