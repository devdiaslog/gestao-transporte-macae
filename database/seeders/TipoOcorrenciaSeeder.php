<?php

namespace Database\Seeders;

use App\Models\TipoOcorrencia;
use Illuminate\Database\Seeder;

class TipoOcorrenciaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'ATRASO DOCUMENTAÇÃO',
            'ATRASO DESCARREGAMENTO',
            'ATRASO CARREGAMENTO',
            'FROTA SEM MOTORISTA',
            'DEMANDA CANCELADA',
            'ATRASO SAÍDA DA FROTA',
            'ATRASO PROGRAMAÇÃO',
            'RECUSA',
            'MANUTENÇÃO',
            'AGUARDANDO PROGRAMAÇÃO',
        ];

        foreach ($tipos as $descricao) {
            TipoOcorrencia::firstOrCreate(['descricao' => $descricao]);
        }
    }
}
