<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Leitura de planilhas exportadas do SAP.
 *
 * Compartilhado pelos importadores: cada um declara seu próprio mapa de
 * colunas e reaproveita aqui o casamento de cabeçalho e a conversão dos
 * formatos do SAP (datas dd.mm.aaaa, números "2.500,50").
 */
trait LeituraPlanilhaSap
{
    /**
     * Lê a planilha e devolve as linhas indexadas pelas chaves do mapa de
     * colunas informado. A chave do array é o número da linha no arquivo.
     *
     * @param  array<string, array<int, string>>  $colunas
     * @return array<int, array<string, string|null>>
     */
    protected function lerPlanilhaSap(string $caminho, array $colunas, int $linhaCabecalho = 1): array
    {
        $reader = new XlsxReader;
        $reader->open($caminho);

        $mapa = [];
        $linhas = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $indice => $row) {
                $celulas = $row->toArray();

                if ($indice < $linhaCabecalho) {
                    continue;
                }

                if ($indice === $linhaCabecalho) {
                    $mapa = $this->mapearCabecalhoSap($celulas, $colunas);

                    continue;
                }

                $linha = [];
                foreach ($mapa as $campo => $posicao) {
                    $valor = $celulas[$posicao] ?? null;
                    $linha[$campo] = $valor instanceof \DateTimeInterface
                        ? $valor->format('d.m.Y H:i:s')
                        : $valor;
                }

                $linhas[$indice] = $linha;
            }

            break; // apenas a primeira aba
        }

        $reader->close();

        return $linhas;
    }

    /**
     * Descobre em que linha está o cabeçalho.
     *
     * O export padrão do SAP reserva as primeiras linhas para a data e o
     * título do relatório; o modelo gerado pelo sistema começa direto no
     * cabeçalho. É a primeira linha que identifica os campos informados.
     *
     * @param  array<string, array<int, string>>  $colunas
     * @param  array<int, string>  $obrigatorios  campos que caracterizam o cabeçalho
     */
    protected function localizarCabecalhoSap(string $caminho, array $colunas, array $obrigatorios): int
    {
        $reader = new XlsxReader;
        $reader->open($caminho);

        $linha = 1;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $indice => $row) {
                if ($indice > 10) {
                    break;
                }

                $mapa = $this->mapearCabecalhoSap($row->toArray(), $colunas);

                if (count(array_intersect($obrigatorios, array_keys($mapa))) === count($obrigatorios)) {
                    $linha = $indice;

                    break 2;
                }
            }

            break;
        }

        $reader->close();

        return $linha;
    }

    /**
     * Casa cada campo interno com a posição da coluna no cabeçalho, por
     * igualdade exata do rótulo (ignorando caixa e acentos).
     *
     * A prioridade é a ordem dos aliases (o mais específico primeiro), não a
     * ordem das colunas: o export do SAP tem "Descrição" (agendamento) antes de
     * "Descrição da carga", e a carga é quem deve vencer.
     *
     * @param  array<int, mixed>  $cabecalho
     * @param  array<string, array<int, string>>  $colunas
     * @return array<string, int>
     */
    protected function mapearCabecalhoSap(array $cabecalho, array $colunas): array
    {
        $normalizado = [];
        foreach ($cabecalho as $posicao => $valor) {
            $n = $this->normalizar((string) $valor);
            if ($n !== '') {
                $normalizado[$posicao] = $n;
            }
        }

        $mapa = [];

        foreach ($colunas as $campo => $rotulos) {
            foreach ($rotulos as $rotulo) {
                $posicao = array_search($this->normalizar($rotulo), $normalizado, true);

                if ($posicao !== false) {
                    $mapa[$campo] = $posicao;
                    // O SAP corta o nome da coluna pela largura dela, o que faz
                    // rótulos diferentes chegarem iguais ("Compriment" da RT e o
                    // da embalagem). Cada coluna atende um campo só, então a
                    // ordem do mapa desempata: o primeiro campo fica com a
                    // primeira ocorrência e o seguinte pega a próxima.
                    unset($normalizado[$posicao]);

                    break;
                }
            }
        }

        return $mapa;
    }

    /**
     * Normaliza um rótulo para comparação: sem acentos, minúsculo, sem espaços extras.
     */
    protected function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim(Str::ascii($texto)));

        return preg_replace('/\s+/', ' ', $texto) ?? $texto;
    }

    /**
     * Combina data (dd.mm.aaaa) e hora do SAP num datetime.
     * Tolera hora com ou sem segundos e data já com hora embutida.
     */
    protected function montarDataHora(?string $data, ?string $hora): ?Carbon
    {
        $data = $this->limpar($data);

        if ($data === null) {
            return null;
        }

        // O SAP pode exportar a data já com hora embutida.
        $data = explode(' ', $data)[0];

        $hora = $this->normalizarHora($hora) ?? '00:00:00';

        foreach (['d.m.Y H:i:s', 'd/m/Y H:i:s', 'Y-m-d H:i:s'] as $formato) {
            try {
                $parsed = Carbon::createFromFormat($formato, "{$data} {$hora}");
            } catch (\Throwable) {
                continue;
            }

            $erros = Carbon::getLastErrors();

            if ($parsed !== false && (! is_array($erros) || $erros['error_count'] === 0)) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Normaliza a hora do SAP para H:i:s ("10:00" → "10:00:00", "10" → "10:00:00").
     */
    protected function normalizarHora(?string $hora): ?string
    {
        $hora = $this->limpar($hora);

        if ($hora === null) {
            return null;
        }

        $hora = explode(' ', $hora)[0];
        $partes = array_pad(explode(':', $hora), 3, '00');

        return implode(':', array_map(
            fn ($p) => str_pad(substr(trim($p), 0, 2), 2, '0', STR_PAD_LEFT),
            array_slice($partes, 0, 3)
        ));
    }

    /**
     * Converte números no formato do SAP ("2.500,50" ou "2500.5") para float.
     */
    protected function numero(?string $valor): ?float
    {
        $valor = $this->limpar($valor);

        if ($valor === null) {
            return null;
        }

        // Formato brasileiro: remove separador de milhar e troca a vírgula decimal.
        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return is_numeric($valor) ? (float) $valor : null;
    }

    protected function limpar(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
