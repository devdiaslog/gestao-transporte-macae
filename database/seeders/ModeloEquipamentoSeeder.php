<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModeloEquipamentoSeeder extends Seeder
{
    public function run(): void
    {
        $motorizado = DB::table('tipos_equipamentos')->where('nome', 'Motorizado')->value('id');
        $implementos = DB::table('tipos_equipamentos')->where('nome', 'Implementos')->value('id');

        $modelos = [
            // Implementos
            ['nome' => 'Prancha Baixa 3E',      'tipo_equipamento_id' => $implementos],
            ['nome' => 'Prancha Baixa 4E',      'tipo_equipamento_id' => $implementos],

            // Motorizados
            ['nome' => 'Cavalo 2544',            'tipo_equipamento_id' => $motorizado],
            ['nome' => 'Cavalo 2644',            'tipo_equipamento_id' => $motorizado],
            ['nome' => 'Cavalo 1933',            'tipo_equipamento_id' => $motorizado],
            ['nome' => 'Caminhão Truck',         'tipo_equipamento_id' => $motorizado],
            ['nome' => 'Caminhão Toco',          'tipo_equipamento_id' => $motorizado],
            ['nome' => 'Caminhão Leve',          'tipo_equipamento_id' => $motorizado],
            ['nome' => 'Caminhão 3/4',           'tipo_equipamento_id' => $motorizado],
            ['nome' => 'Caminhão Truck Sider',   'tipo_equipamento_id' => $motorizado],
        ];

        foreach ($modelos as $modelo) {
            DB::table('modelos_equipamentos')->insertOrIgnore([
                'tipo_equipamento_id' => $modelo['tipo_equipamento_id'],
                'nome' => $modelo['nome'],
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
