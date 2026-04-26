<?php

namespace Database\Seeders;

use App\Enums\TipoResponsavel;
use App\Models\Responsavel;
use Illuminate\Database\Seeder;

class ResponsavelSeeder extends Seeder
{
    public function run(): void
    {
        $internos = [
            'CELULA FISCAL',
            'FROTA POLI',
            'FROTA PROGRAMAÇÃO',
            'MANUTENÇÃO MACAÉ',
        ];

        $externos = [
            'PORTO DE MACAÉ (BMAC)',
            'ARM-MACAÉ - AL-13',
            'ARM-MACAÉ - TRIAGEM',
            'PORTO DO RIO',
            'ARM RIO',
            'ARM-RIO - TRIAGEM',
            'PARQUE DE TUBOS - ANCORAGEM',
            'PORTO DO AÇU -TMUT',
            'PORTO DO AÇU',
            'ARM-MACAÉ',
            'ARM-MACAÉ - AL-17',
            'ARM-MACAÉ - AL-26',
            'ARM-MACAÉ - AL-33',
            'ARM-MACAÉ - SUCATA',
            'ARM-MACAÉ - DOCAS',
        ];

        foreach ($internos as $nome) {
            Responsavel::firstOrCreate(
                ['nome' => $nome],
                ['tipo' => TipoResponsavel::Interno, 'ativo' => true],
            );
        }

        foreach ($externos as $nome) {
            Responsavel::firstOrCreate(
                ['nome' => $nome],
                ['tipo' => TipoResponsavel::Externo, 'ativo' => true],
            );
        }
    }
}
