<?php

namespace App\Support;

use App\Models\DemandaItem;

/**
 * Medidas da embalagem superior (unitização).
 *
 * O SAP entrega comprimento, largura e altura da unitização em colunas
 * próprias — é essa a fonte. A descrição do contentor às vezes repete as
 * medidas no texto, em formatos variados:
 *
 *     CISA1010093 Container 3MDry(3,0x2,4x2,4)
 *     CISA0871013 Cesta 2,40M (2,40x2,00x2,00)
 *     200034090 Skid (1,20 X 1,20 X 2,30 )
 *     CISA2010208 Container6MDry6,05x2,43x2,45
 *
 * A extração do texto serve apenas de reserva, para quando alguma das colunas
 * não vier no layout exportado.
 */
class ContentorSap
{
    /**
     * Comprimento, largura e altura em metros extraídos da descrição, ou null
     * quando ela não os traz de forma reconhecível.
     *
     * @return array{comprimento: float, largura: float, altura: float}|null
     */
    public static function dimensoes(?string $descricao): ?array
    {
        if (trim((string) $descricao) === '') {
            return null;
        }

        // Três números separados por "x", com ou sem parênteses e espaços.
        $padrao = '/([\d]+[.,]?[\d]*)\s*[xX×]\s*([\d]+[.,]?[\d]*)\s*[xX×]\s*([\d]+[.,]?[\d]*)/u';

        if (! preg_match($padrao, $descricao, $m)) {
            return null;
        }

        $numero = fn (string $v): float => (float) str_replace(',', '.', $v);

        return [
            'comprimento' => $numero($m[1]),
            'largura' => $numero($m[2]),
            'altura' => $numero($m[3]),
        ];
    }

    /**
     * Área de piso da embalagem em m² — comprimento × largura.
     *
     * Prefere as medidas que o SAP mandou em coluna; só recorre à descrição
     * quando alguma delas falta.
     */
    public static function area(?float $comprimento, ?float $largura, ?string $descricao = null): ?float
    {
        if ($comprimento > 0 && $largura > 0) {
            return round($comprimento * $largura, 2);
        }

        $doTexto = self::dimensoes($descricao);

        if ($doTexto === null || $doTexto['comprimento'] <= 0 || $doTexto['largura'] <= 0) {
            return null;
        }

        return round($doTexto['comprimento'] * $doTexto['largura'], 2);
    }

    /**
     * Área de piso de um conjunto de itens, em m².
     *
     * Cada contentor entra uma única vez, por mais itens que carregue dentro:
     * dez caixas no mesmo contentor ocupam o espaço do contentor, não a soma
     * das dez. Itens fora de contentor entram pelas próprias medidas.
     *
     * Um contentor é montado para um único destino, então nunca se divide
     * entre trechos — somar por trecho não corre risco de duplicar.
     *
     * @param  iterable<DemandaItem>  $itens
     */
    public static function areaDePiso(iterable $itens): float
    {
        $total = 0.0;
        $contentoresContados = [];

        foreach ($itens as $item) {
            // Medida que veio do SAP em outra unidade estragaria o total.
            if ($item->medidaSuspeita()) {
                continue;
            }

            $embalagem = $item->embalagemSuperior();

            if ($embalagem === null) {
                $total += $item->area() ?? 0;

                continue;
            }

            if (isset($contentoresContados[$embalagem])) {
                continue;
            }

            $contentoresContados[$embalagem] = true;

            // Embalagem sem medidas conhecidas: vale o que o item ocupa, para
            // não zerar a área do grupo inteiro.
            $total += $item->area_embalagem !== null
                ? (float) $item->area_embalagem
                : ($item->area() ?? 0);
        }

        return round($total, 2);
    }
}
