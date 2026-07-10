<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalSeeder extends Seeder
{
    public function run(): void
    {
        $locais = [
            'Porto de Macaé',
            'Base Poli Macaé',
            'Pátio de Espera Macaé',
            'Terminal de Cargas Macaé',
            'TRANSPETRO Macaé',
            'Base SLB Macaé',
            'Base Halliburton Macaé',
            'Base Baker Hughes Macaé',
            'Aeroporto de Macaé',
            'Base Rio das Ostras',
            'Pátio Externo Norte',
            'Pátio Externo Sul',
            'Oficina Central',
        ];

        foreach ($locais as $nome) {
            DB::table('locais')->insertOrIgnore([
                'nome' => $nome,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
