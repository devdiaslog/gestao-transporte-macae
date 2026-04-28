<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DivisaoSeeder::class,
            TipoEquipamentoSeeder::class,
            ModeloEquipamentoSeeder::class,
            StatusOperacionalSeeder::class,
            ResponsavelSeeder::class,
            TipoOcorrenciaSeeder::class,
            JustificativaSeeder::class,
        ]);
    }
}
