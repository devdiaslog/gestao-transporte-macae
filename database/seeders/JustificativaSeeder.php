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
            ['descricao' => 'CORREÇÃO DE NOTA FISCAL',                  'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO VEZ PARA ATENDIMENTO',          'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO VEZ NO CARREGAMENTO',           'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO AGENDAMENTO',                   'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO PROTOCOLAR NOTAS',              'obrigar_observacao' => false],
            ['descricao' => 'FALTA DE MOTORISTA NA ESCALA',             'obrigar_observacao' => false],
            ['descricao' => 'RECUSA - QUANTIDADE DIVERGENTE DO SOLICITADO', 'obrigar_observacao' => true],
            ['descricao' => 'RECUSA - FALTA DE RESERVA',                'obrigar_observacao' => true],
            ['descricao' => 'AGUARDANDO EMISSÃO DE NOTA FISCAL',        'obrigar_observacao' => false],
            ['descricao' => 'DEMANDA SERÁ REPROGRAMADA',                'obrigar_observacao' => false],
            ['descricao' => 'EQUIPAMENTO INADEQUADO PARA DEMANDA',      'obrigar_observacao' => true],
            ['descricao' => 'OBSTRUÇÃO DE VIA',                         'obrigar_observacao' => true],
            ['descricao' => 'AGUARDANDO DISPONIBILIDADE DE ESCOLTA',    'obrigar_observacao' => false],
            ['descricao' => 'MATERIAL NÃO DISPONÍVEL',                  'obrigar_observacao' => false],
            ['descricao' => 'AGENDAMENTO CANCELADO',                    'obrigar_observacao' => true],
            ['descricao' => 'EQUIPE RESPONSÁVEL AUSENTE',               'obrigar_observacao' => false],
            ['descricao' => 'PROBLEMAS MECÂNICOS',                      'obrigar_observacao' => true],
            ['descricao' => 'AGUARDANDO EMISSÃO DE AGENDAMENTO',        'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO EMISSÃO ATENDIMENTO',           'obrigar_observacao' => false],
            ['descricao' => 'RECUSA NA AMARRAÇÃO DE CARGA',             'obrigar_observacao' => true],
            ['descricao' => 'DEMANDA BAIXA',                            'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO DEMANDA PORTE ESPECIAL',        'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO EMISSÃO DE CTE',                'obrigar_observacao' => false],
            ['descricao' => 'MATERIAL DIVERGENTE',                      'obrigar_observacao' => true],
            ['descricao' => 'ATRASO/FALTA DE GUINDASTE',                'obrigar_observacao' => true],
            ['descricao' => 'MANUTENÇÃO PREVENTIVA',                    'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO ATENDIMENTO NA BASE',           'obrigar_observacao' => false],
            ['descricao' => 'AGUARDANDO RECUSA ELETRÔNICA',             'obrigar_observacao' => false],
        ];

        foreach ($justificativas as $data) {
            Justificativa::firstOrCreate(
                ['descricao' => $data['descricao']],
                ['obrigar_observacao' => $data['obrigar_observacao'], 'ativo' => true],
            );
        }
    }
}
