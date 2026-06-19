<?php

namespace Database\Seeders;

use App\Models\LocalEtapa;
use Illuminate\Database\Seeder;

class LocalEtapaSeeder extends Seeder
{
    /** @var list<string> */
    private array $locais = [
        // ARM-MACAÉ — almoxarifados e setores
        'ARM-MACAÉ - AL-02',
        'ARM-MACAÉ - AL-06',
        'ARM-MACAÉ - AL-13',
        'ARM-MACAÉ - AL-17',
        'ARM-MACAÉ - AL-20',
        'ARM-MACAÉ - AL-26',
        'ARM-MACAÉ - AL-33',
        'ARM-MACAÉ - AE-20',
        'ARM-MACAÉ - Área Externa',
        'ARM-MACAÉ - Triagem',
        'ARM-MACAÉ - Preservação de Contentores',
        'ARM-MACAÉ - Sucata',
        'ARM-MACAÉ - Docas',
        'ARM-MACAÉ - BR Morro',
        // ARM-RIO
        'ARM-RIO - Área Externa',
        // Portos
        'Porto de Macaé (BMAC)',
        'Porto de Macaé - Imbetiba',
        'Porto do Rio (PBG)',
        'Porto do Açu (AÇU)',
        'Porto do Açu - DOME PACU',
        // Internos / operacionais
        'Célula Fiscal',
        'Manutenção Macaé',
        'Terminal Cabiúnas',
        'Frota Poli Macaé',
        'Frota Programação',
        'Macaé Diversos',
        // Empresas externas — clientes e parceiros
        'Actemium',
        'Artrex',
        'Backer',
        'BAVIT',
        'Belov (Guapimirim)',
        'CENPES',
        'COMPERJ',
        'FMC',
        'Fronape',
        'Gran Services',
        'Halliburton',
        'Manserv',
        'Marine',
        'Oceânica',
        'OCYAN',
        'One Subsea',
        'Supply',
        'Weatherford',
    ];

    public function run(): void
    {
        LocalEtapa::truncate();

        foreach ($this->locais as $nome) {
            LocalEtapa::create(['nome' => $nome, 'ativo' => true]);
        }
    }
}
