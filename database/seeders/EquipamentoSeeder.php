<?php

namespace Database\Seeders;

use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\TipoEquipamento;
use Illuminate\Database\Seeder;

class EquipamentoSeeder extends Seeder
{
    public function run(): void
    {
        $divisao = Divisao::where('nome', 'Poli Macaé')->firstOrFail();
        $tipo = TipoEquipamento::where('nome', 'Motorizado')->firstOrFail();

        $equipamentos = [
            ['placa' => 'QRF9E55', 'prefixo' => '1803',  'id_rastreador' => 'QRF9E55'],
            ['placa' => 'QRF9E56', 'prefixo' => '1804',  'id_rastreador' => 'QRF9E56'],
            ['placa' => 'QRI5J06', 'prefixo' => '1806',  'id_rastreador' => 'QRI5J06'],
            ['placa' => 'QRI5J51', 'prefixo' => '1807',  'id_rastreador' => 'QRI5J51'],
            ['placa' => 'QRI7F20', 'prefixo' => '1813',  'id_rastreador' => 'QRI7F20'],
            ['placa' => 'QRI5J65', 'prefixo' => '1831C', 'id_rastreador' => 'QRI5J65'],
            ['placa' => 'QRD1015', 'prefixo' => '1834',  'id_rastreador' => 'QRD1015'],
            ['placa' => 'QRD1012', 'prefixo' => '1835',  'id_rastreador' => 'QRD1012'],
            ['placa' => 'QRI7F32', 'prefixo' => '1840',  'id_rastreador' => 'QRI7F32'],
            ['placa' => 'QRD0996', 'prefixo' => '1846',  'id_rastreador' => 'QRD0996'],
            ['placa' => 'QRD1013', 'prefixo' => '1853',  'id_rastreador' => 'QRD1013'],
            ['placa' => 'QRF9E51', 'prefixo' => '1854',  'id_rastreador' => 'QRF9E51'],
            ['placa' => 'QRF9E50', 'prefixo' => '1858',  'id_rastreador' => 'QRF9E50'],
            ['placa' => 'QRI5J62', 'prefixo' => '1859',  'id_rastreador' => 'QRI5J62'],
            ['placa' => 'QRK4H84', 'prefixo' => '1870',  'id_rastreador' => 'QRK4H84'],
            ['placa' => 'QRH7B06', 'prefixo' => '1872',  'id_rastreador' => 'QRH7B06'],
            ['placa' => 'QRE5H79', 'prefixo' => '1904',  'id_rastreador' => 'QRE5H79'],
            ['placa' => 'QRI8I86', 'prefixo' => '1929',  'id_rastreador' => 'QRI8I86'],
            ['placa' => 'PPJ0398',  'prefixo' => '1933',  'id_rastreador' => 'PPJ0398'],
            ['placa' => 'QRJ3H97', 'prefixo' => '1936',  'id_rastreador' => 'QRJ3H97'],
            ['placa' => 'PPJ0396',  'prefixo' => '1938',  'id_rastreador' => 'PPJ0396'],
            ['placa' => 'PPJ0399',  'prefixo' => '1939',  'id_rastreador' => 'PPJ0399'],
            ['placa' => 'RBB8E17', 'prefixo' => '1941',  'id_rastreador' => 'RBB8E17'],
            ['placa' => 'RBJ7C14', 'prefixo' => '1948',  'id_rastreador' => 'RBJ7C14'],
            ['placa' => 'QRI7F21', 'prefixo' => '1961',  'id_rastreador' => 'QRI7F21'],
            ['placa' => 'QRI7F09', 'prefixo' => '1962',  'id_rastreador' => 'QRI7F09'],
            ['placa' => 'QRI7F12', 'prefixo' => '1964',  'id_rastreador' => 'QRI7F12'],
            ['placa' => 'RBH6F81', 'prefixo' => '1965',  'id_rastreador' => 'RBH6F81'],
            ['placa' => 'RBI8F01', 'prefixo' => '1969',  'id_rastreador' => 'RBI8F01'],
            ['placa' => 'RBH6J16', 'prefixo' => '1972',  'id_rastreador' => 'RBH6J16'],
            ['placa' => 'RBH6J08', 'prefixo' => '1973',  'id_rastreador' => 'RBH6J08'],
            ['placa' => 'RBH6J12', 'prefixo' => '1976',  'id_rastreador' => 'RBH6J12'],
            ['placa' => 'PLD2436',  'prefixo' => '1980',  'id_rastreador' => 'PLD2436'],
            ['placa' => 'QRI5J50', 'prefixo' => '1984R', 'id_rastreador' => 'QRI5J50'],
            ['placa' => 'QRD1011', 'prefixo' => '1986R', 'id_rastreador' => 'QRD1011'],
            ['placa' => 'QRD1007', 'prefixo' => '1988',  'id_rastreador' => 'QRD1007'],
            ['placa' => 'QRD1002', 'prefixo' => '1992',  'id_rastreador' => 'QRD1002'],
            ['placa' => 'QRD1008', 'prefixo' => '1993',  'id_rastreador' => 'QRD1008'],
            ['placa' => 'QRD0994', 'prefixo' => '1994',  'id_rastreador' => 'QRD0994'],
            ['placa' => 'QRI9H62', 'prefixo' => '1996',  'id_rastreador' => 'QRI9H62'],
            ['placa' => 'SYO6D12', 'prefixo' => '1997',  'id_rastreador' => 'SYO6D12'],
            ['placa' => 'RUK8I22', 'prefixo' => '1998',  'id_rastreador' => 'RUK8I22'],
            ['placa' => 'RUX7B90', 'prefixo' => '1999',  'id_rastreador' => 'RUX7B90'],
            ['placa' => 'SFV6D62', 'prefixo' => '2000',  'id_rastreador' => 'SFV6D62'],
            ['placa' => 'EEA7F28', 'prefixo' => '2001',  'id_rastreador' => 'EEA7F28'],
            ['placa' => 'RQP3H07', 'prefixo' => '2002',  'id_rastreador' => 'RQP3H07'],
            ['placa' => 'RQP3H18', 'prefixo' => '2003',  'id_rastreador' => 'RQP3H18'],
            ['placa' => 'RQP2F22', 'prefixo' => '2004',  'id_rastreador' => 'RQP2F22'],
            ['placa' => 'RQP3H13', 'prefixo' => '2006',  'id_rastreador' => 'RQP3H13'],
            ['placa' => 'SGE1C70', 'prefixo' => '2007',  'id_rastreador' => 'SGE1C70'],
            ['placa' => 'RQP3H02', 'prefixo' => '2008',  'id_rastreador' => 'RQP3H02'],
            ['placa' => 'RBJ0F41', 'prefixo' => '2009',  'id_rastreador' => 'RBJ0F41'],
            ['placa' => 'RBJ0F39', 'prefixo' => '2010',  'id_rastreador' => 'RBJ0F39'],
            ['placa' => 'RBJ0F44', 'prefixo' => '2011',  'id_rastreador' => 'RBJ0F44'],
            ['placa' => 'RBJ0G94', 'prefixo' => '2012',  'id_rastreador' => 'RBJ0G94'],
            ['placa' => 'RMK6C91', 'prefixo' => '2015',  'id_rastreador' => 'RMK6C91'],
            ['placa' => 'RQS1I09', 'prefixo' => '2021',  'id_rastreador' => 'RQS1I09'],
            ['placa' => 'FZT1E75', 'prefixo' => '2025',  'id_rastreador' => 'FZT1E75'],
            ['placa' => 'GJH5H96', 'prefixo' => '2026',  'id_rastreador' => 'GJH5H96'],
            ['placa' => 'GGW9F51', 'prefixo' => '2027',  'id_rastreador' => 'GGW9F51'],
            ['placa' => 'SFS9B45', 'prefixo' => '2028',  'id_rastreador' => 'SFS9B45'],
            ['placa' => 'SFS9B51', 'prefixo' => '2029',  'id_rastreador' => 'SFS9B51'],
            ['placa' => 'SFU6D52', 'prefixo' => '2030',  'id_rastreador' => 'SFU6D52'],
            ['placa' => 'QRK4H83', 'prefixo' => '2031',  'id_rastreador' => 'QRK4H83'],
            ['placa' => 'SFS9B53', 'prefixo' => '371',   'id_rastreador' => 'SFS9B53'],
            ['placa' => 'RBH6J27', 'prefixo' => '807',   'id_rastreador' => 'RBH6J27'],
            ['placa' => 'RBH6J06', 'prefixo' => '809',   'id_rastreador' => 'RBH6J06'],
            ['placa' => 'QRD1006', 'prefixo' => '813',   'id_rastreador' => 'QRD1006'],
            ['placa' => 'QRI7E99', 'prefixo' => '821',   'id_rastreador' => 'QRI7E99'],
            ['placa' => 'QRI7E98', 'prefixo' => '823',   'id_rastreador' => 'QRI7E98'],
            ['placa' => 'RQP2F23', 'prefixo' => '833',   'id_rastreador' => 'RQP2F23'],
            ['placa' => 'QRH3C08', 'prefixo' => '851',   'id_rastreador' => 'QRH3C08'],
            ['placa' => 'RBH0E71', 'prefixo' => '853',   'id_rastreador' => 'RBH0E71'],
        ];

        foreach ($equipamentos as $data) {
            Equipamento::firstOrCreate(
                ['placa' => $data['placa']],
                [
                    'prefixo' => $data['prefixo'],
                    'id_rastreador' => $data['id_rastreador'],
                    'tipo_id' => $tipo->id,
                    'divisao_id' => $divisao->id,
                    'status' => true,
                ],
            );
        }
    }
}
