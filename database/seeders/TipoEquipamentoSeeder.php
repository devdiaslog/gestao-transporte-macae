<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoEquipamentoSeeder extends Seeder
{
    public function run(): void
    {
        $nomes = [
            'Motorizado',
            'Implementos',
        ];

        foreach ($nomes as $nome) {
            DB::table('tipos_equipamentos')->insertOrIgnore([
                'nome' => $nome,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
