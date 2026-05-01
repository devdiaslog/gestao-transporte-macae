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
            'ATRASO SAÍDA DA FROTA',
            'ATRASO TRATATIVA DE RECUSA',
            'ATRASO POR MANUTENÇÃO',
        ];

        foreach ($tipos as $descricao) {
            TipoOcorrencia::firstOrCreate(['descricao' => $descricao]);
        }
    }
}
