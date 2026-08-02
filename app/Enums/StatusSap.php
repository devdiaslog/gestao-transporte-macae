<?php

namespace App\Enums;

/**
 * Ciclo de vida do item de entrega no SAP.
 *
 * Ao contrário de {@see StatusItemDemanda} — que é o status operacional que a
 * torre controla — aqui os valores são os próprios códigos do SAP, porque a
 * distinção entre 13 e 18 importa para o negócio: ambos suspendem o item, mas
 * apenas o 18 é de responsabilidade do cliente.
 */
enum StatusSap: string
{
    case Liberado = '03';
    case Programado = '04';
    case Atendido = '07';
    case Cancelado = '09';
    case SuspensoInterno = '13';
    case SuspensoExterno = '18';

    public function label(): string
    {
        return match ($this) {
            self::Liberado => 'Liberado',
            self::Programado => 'Programado',
            self::Atendido => 'Atendido',
            self::Cancelado => 'Cancelado',
            self::SuspensoInterno => 'Suspenso',
            self::SuspensoExterno => 'Suspenso — Fator Externo',
        };
    }

    /**
     * Texto de apoio exibido junto ao status nas telas.
     */
    public function descricao(): string
    {
        return match ($this) {
            self::Liberado => 'Liberado pelo cliente para o transporte',
            self::Programado => 'Veículo selecionado pela programação',
            self::Atendido => 'Entrega realizada no físico e registrada no SAP',
            self::Cancelado => 'Transporte do item cancelado',
            self::SuspensoInterno => 'Parado por fator interno do transporte',
            self::SuspensoExterno => 'Parado por fator do cliente (material indisponível, unitização, dados incorretos)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Liberado => 'sky',
            self::Programado => 'indigo',
            self::Atendido => 'emerald',
            self::Cancelado => 'zinc',
            self::SuspensoInterno => 'amber',
            self::SuspensoExterno => 'orange',
        };
    }

    /**
     * O cliente ainda cobra entrega e previsão: o item não foi atendido,
     * cancelado nem suspenso.
     */
    public function emCobranca(): bool
    {
        return in_array($this, [self::Liberado, self::Programado], true);
    }

    /**
     * Item parado que pode voltar a andar — e, quando voltar, possivelmente
     * com um prazo novo.
     */
    public function suspenso(): bool
    {
        return in_array($this, [self::SuspensoInterno, self::SuspensoExterno], true);
    }

    /**
     * Ciclo encerrado em definitivo: não retorna para a fila de atendimento.
     */
    public function encerrado(): bool
    {
        return in_array($this, [self::Atendido, self::Cancelado], true);
    }

    /**
     * A suspensão é de responsabilidade do cliente (código 18) e não nossa.
     * Separa os contadores que o gerente apresenta ao cliente.
     */
    public function responsabilidadeCliente(): bool
    {
        return $this === self::SuspensoExterno;
    }

    /**
     * Traduz o código bruto do SAP, tolerando valores de um dígito ("4" vira
     * "04") e devolvendo null para códigos desconhecidos.
     */
    public static function fromCodigo(string|int|null $codigo): ?self
    {
        if ($codigo === null || $codigo === '') {
            return null;
        }

        return self::tryFrom(str_pad((string) $codigo, 2, '0', STR_PAD_LEFT));
    }
}
