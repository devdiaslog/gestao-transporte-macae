<?php

namespace Database\Seeders;

use App\Models\Justificativa;
use Illuminate\Database\Seeder;

class JustificativaSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Justificativas com obrigar_observacao=true são as que no CSV
         * sempre vinham acompanhadas de texto na coluna OBSERVAÇÃO.
         *
         * @var array<int, array{descricao: string, obrigar_observacao: bool}>
         */
        $justificativas = [
            ['descricao' => 'MOTORISTA - FALTA DE MOTORISTA NA ESCALA', 'obrigar_observacao' => false],
            ['descricao' => 'MOTORISTA - MOTORISTA BLOQUEADO TRATATIVA', 'obrigar_observacao' => false],
            ['descricao' => 'MOTORISTA - ESCALA DIVERENCIADA', 'obrigar_observacao' => false],

            ['descricao' => 'CORREÇÃO - NOTA FISCAL',                  'obrigar_observacao' => false],

            ['descricao' => 'LIBERAÇÃO - AMARRAÇÃO DE CARGA',             'obrigar_observacao' => true],
            ['descricao' => 'LIBERAÇÃO - GERENCIAMENTO DE VIAGEM',             'obrigar_observacao' => true],

            ['descricao' => 'ESPERA - FILA DE CARREGAMENTO',          'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - FILA DE DESCARREGAMENTO',           'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - HORA DO AGENDAMENTO',                   'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - CRIAR AGENDAMENTO',                   'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - PROTOCOLAR NOTAS',              'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - DISPONIBILIDADE DE ESCOLTA',    'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - VIAS/ATENDIMENTO CARGA',           'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - PLANEJAMENTO PORTE ESPECIAL',        'obrigar_observacao' => false],
            ['descricao' => 'ESPERA - EMISSÃO DE CTE',                'obrigar_observacao' => false],

            ['descricao' => 'RECUSA - QUANTIDADE DIVERGENTE', 'obrigar_observacao' => true],
            ['descricao' => 'RECUSA - DESCRIÇÃO DIVERGENTE', 'obrigar_observacao' => true],
            ['descricao' => 'RECUSA - DESTINO DIVERGENTE',                'obrigar_observacao' => true],
            ['descricao' => 'RECUSA - RECUSA NOTA FISCAL ELETRONICA',                'obrigar_observacao' => true],
            ['descricao' => 'RECUSA - DOCUMENTAÇÃO NECESSÁRIA',                'obrigar_observacao' => true],


            ['descricao' => 'EQUIPAMENTO - INADEQUADO PARA DEMANDA',      'obrigar_observacao' => true],
            ['descricao' => 'VIAS - OBSTRUÇÃO DE VIA',                         'obrigar_observacao' => true],

            ['descricao' => 'SETOR - MATERIAL NÃO DISPONÍVEL',                  'obrigar_observacao' => false],
            ['descricao' => 'SETOR - AGENDAMENTO CANCELADO',                    'obrigar_observacao' => true],
            ['descricao' => 'SETOR - EQUIPE RESPONSÁVEL AUSENTE',               'obrigar_observacao' => false],
            ['descricao' => 'SETOR - FALTA DE GUINDASTE',                'obrigar_observacao' => true],
            ['descricao' => 'SETOR - FALTA DE EMPILHADEIRA',                'obrigar_observacao' => true],

            ['descricao' => 'MANUTENÇÃO - CORRETIVA',                     'obrigar_observacao' => true],
            ['descricao' => 'MANUTENÇÃO - PREVENTIVA',                    'obrigar_observacao' => false],
            ['descricao' => 'MANUTENÇÃO - CERTIFICADO CIV',              'obrigar_observacao' => false],

        ];

        foreach ($justificativas as $data) {
            Justificativa::firstOrCreate(
                ['descricao' => $data['descricao']],
                ['obrigar_observacao' => $data['obrigar_observacao'], 'ativo' => true],
            );
        }
    }
}
