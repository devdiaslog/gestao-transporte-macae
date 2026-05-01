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
            'PORTO DO AÇU (AÇU)',
            'PORTO DO RIO (PBG)',
            'PORTO DO AÇU - TMUT',
            'PORTO DO AÇU - DOME',

            'ARM-MACAÉ - AL-06',
            'ARM-MACAÉ - AL-13',
            'ARM-MACAÉ - AL-17',
            'ARM-MACAÉ - AL-26',
            'ARM-MACAÉ - AL-33',
            'ARM-MACAÉ - AE-20',
            'ARM-MACAÉ - TRIAGEM',
            'ARM-MACAÉ - PRESERVAÇÃO DE CONTENTORES',
            'ARM-MACAÉ - ÁREA EXTERNA',
            'ARM-MACAÉ - SUCATA',
            'ARM-MACAÉ - DOCAS',

            'PARQUE DE TUBOS - ANCORAGEM',
            'PARQUE DE TUBOS - OTBM',

            'ARM-RIO - TRIAGEM',
            'ARM-RIO - ÁREA EXTERNA',

            'MACAÉ DIVERSOS',
            'RIO DIVERSOS',

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
