<?php

namespace App\Enums;

enum AtividadeCerca: string
{
    case Estacionamento = 'Estacionamento';
    case CargaDescarga = 'Carga/Descarga';
    case Carga = 'Carga';
    case Descarga = 'Descarga';
    case Deposito = 'Depósito';
    case Abastecimento = 'Abastecimento';
    case Manutencao = 'Manutenção';
    case Lavagem = 'Lavagem';
    case Borracharia = 'Borracharia';
    case Pernoite = 'Pernoite';
    case BaseOperacional = 'Base Operacional';
    case PontoPedagio = 'Pedágio';
    case Porto = 'Porto';
    case Terminal = 'Terminal';

    public function label(): string
    {
        return $this->value;
    }
}
