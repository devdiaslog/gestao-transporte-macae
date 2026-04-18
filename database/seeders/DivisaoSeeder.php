<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisaoSeeder extends Seeder
{
    public function run(): void
    {
        $nomes = [
            'Poli Macaé',
            'Poli Rio',
            'TT Macaé',
            'TT Rio',
        ];

        foreach ($nomes as $nome) {
            DB::table('divisoes')->insertOrIgnore([
                'nome' => $nome,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
