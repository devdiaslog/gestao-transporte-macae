<?php

namespace Database\Seeders;

use App\Models\TipoEtapa;
use Illuminate\Database\Seeder;

class TipoEtapaSeeder extends Seeder
{
    /** @var list<string> */
    private array $tipos = [
        'Ag. Carregamento',
        'Ag. Descarregamento',
        'Ag. Documentação',
        'Ag. Saída da Frota',
        'Ag. Programação',
        'Ag. Motorista',
        'Ag. Alocação de Demanda',
        'Manutenção',
        'Tratativa de Recusa',
    ];

    public function run(): void
    {
        foreach ($this->tipos as $nome) {
            TipoEtapa::firstOrCreate(['nome' => $nome], ['ativo' => true]);
        }
    }
}
