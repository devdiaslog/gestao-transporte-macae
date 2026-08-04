<?php

namespace App\Enums;

/**
 * Ciclo de vida do item de entrega no SAP.
 *
 * Ao contrário de {@see StatusItemDemanda} — que é o status operacional que a
 * torre controla — aqui os valores são os próprios códigos do SAP, porque a
 * distinção entre 13 e 18 importa para o negócio: ambos suspendem o item, mas
 * apenas o 18 é de responsabilidade do cliente.
 *
 * A chave do item no SAP é RT + Item + Subitem e nunca se repete: o Item é o
 * material e o Subitem é a etapa origem→destino. Um subitem suspenso permanece
 * suspenso; quando o impedimento é resolvido, nasce um subitem novo.
 */
enum StatusSap: string
{
    case Liberado = '03';
    case Programado = '04';
    case Faltoso = '10';
    case Atendido = '07';
    case Cancelado = '09';
    case SuspensoInterno = '13';
    case SuspensoExterno = '18';

    public function label(): string
    {
        return match ($this) {
            self::Liberado => 'Liberado',
            self::Programado => 'Programado',
            self::Faltoso => 'Faltoso',
            self::Atendido => 'Atendido',
            self::Cancelado => 'Cancelado',
            self::SuspensoInterno => 'Suspenso interno',
            self::SuspensoExterno => 'Suspenso cliente',
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
            self::Faltoso => 'Aguardando o solicitante acertar uma pendência do pedido',
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
            self::Faltoso => 'rose',
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
        return in_array($this, [self::Liberado, self::Programado, self::Faltoso], true);
    }

    /**
     * O pedido tem uma pendência do solicitante — falta detalhar itens,
     * destino, pessoa de contato. O transporte é nosso e o item continua vivo;
     * só não anda enquanto a pendência não for acertada. Passado o tempo de
     * espera sem acerto, vira suspensão de responsabilidade do cliente (18).
     */
    public function faltoso(): bool
    {
        return $this === self::Faltoso;
    }

    /**
     * Item parado.
     *
     * O subitem suspenso não volta a andar: ele permanece nesse estado como
     * base de cobrança, e quando o impedimento é resolvido o cliente abre um
     * subitem novo — que entra no sistema como outro item, com prazo próprio.
     */
    public function suspenso(): bool
    {
        return in_array($this, [self::SuspensoInterno, self::SuspensoExterno], true);
    }

    /**
     * Ciclo encerrado: o item não retorna para a fila de atendimento.
     *
     * Inclui os suspensos porque o subitem em si não anda mais — o trabalho
     * reaparece como subitem novo, nunca reativando este.
     */
    public function encerrado(): bool
    {
        return ! $this->emCobranca();
    }

    /**
     * A suspensão é de responsabilidade do cliente (código 18) e não nossa.
     *
     * É o item que a operação mantém suspenso para faturar: não fomos nós que
     * deixamos de entregar. Separa os contadores que o gerente apresenta ao
     * cliente e a base do que pode ser cobrado.
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
