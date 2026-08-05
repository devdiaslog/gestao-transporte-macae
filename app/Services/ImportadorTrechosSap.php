<?php

namespace App\Services;

use App\Models\TrechoSap;
use App\Traits\LeituraPlanilhaSap;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Importa a tabela de prazos por trecho.
 *
 * A planilha costuma trazer a mesma origem→destino em várias linhas, com
 * quilometragens e prazos diferentes — acontece quando o nome do local é um
 * agrupamento ("BASES EXTERNAS" cobre de 13 km a 365 km). Como o trecho é
 * único por par de locais, escolher uma das linhas em silêncio aplicaria o
 * prazo errado ao item: a importação recusa o arquivo inteiro e devolve os
 * conflitos para a operação decidir na planilha.
 */
class ImportadorTrechosSap
{
    use LeituraPlanilhaSap;

    /** @var array<string, array<int, string>> */
    private const COLUNAS = [
        'origem_sap' => ['Origem SAP', 'Origem'],
        'destino_sap' => ['Destino SAP', 'Destino'],
        'cidade_origem' => ['Cidade Origem', 'Cidade de Origem', 'Municipio Origem', 'Município Origem'],
        'cidade_destino' => ['Cidade Destino', 'Cidade de Destino', 'Municipio Destino', 'Município Destino'],
        'km_trecho' => ['Distância (km)', 'Distancia (km)', 'Distância', 'KM', 'Km Trecho'],
        'prazo_horas_normal' => ['Prazo Hora Normal', 'Prazo Horas Normal', 'Prazo Normal'],
        'prazo_horas_expresso' => ['Prazo Hora Expresso', 'Prazo Horas Expresso', 'Prazo Expresso'],
    ];

    /** @var array<int, string> */
    private const CABECALHO_MODELO = [
        'Origem SAP',
        'Destino SAP',
        'Cidade Origem',
        'Cidade Destino',
        'Distância (km)',
        'Prazo Hora Normal',
        'Prazo Hora Expresso',
    ];

    /**
     * @return array{criados: int, atualizados: int, inalterados: int, linhas_ignoradas: int, erros: array<int, string>, conflitos: array<int, string>}
     */
    public function importar(string $caminho, ?int $usuarioId = null): array
    {
        $resultado = [
            'criados' => 0,
            'atualizados' => 0,
            'inalterados' => 0,
            'linhas_ignoradas' => 0,
            'erros' => [],
            'conflitos' => [],
        ];

        $cabecalho = $this->localizarCabecalhoSap($caminho, self::COLUNAS, ['origem_sap', 'destino_sap']);
        $linhas = $this->lerPlanilhaSap($caminho, self::COLUNAS, $cabecalho);

        if ($linhas === []) {
            $resultado['erros'][] = 'A planilha não tem linhas de dados abaixo do cabeçalho.';

            return $resultado;
        }

        $porChave = $this->agruparPorChave($linhas, $resultado);

        $resultado['conflitos'] = $this->conflitos($porChave);

        // Conflito não é aviso: enquanto houver, nada entra.
        if ($resultado['conflitos'] !== []) {
            return $resultado;
        }

        DB::transaction(function () use ($porChave, $usuarioId, &$resultado) {
            foreach ($porChave as $chave => $linhasDaChave) {
                $dados = $linhasDaChave[0];

                $trecho = TrechoSap::withTrashed()->firstOrNew(['chave_origem_destino' => $chave]);
                $novo = ! $trecho->exists;

                $trecho->origem_sap = $dados['origem_sap'];
                $trecho->destino_sap = $dados['destino_sap'];
                $trecho->km_trecho = $dados['km_trecho'];

                // Cidade é descritiva e pode faltar em algumas linhas: só
                // sobrescreve quando a planilha traz o dado, para não apagar o
                // que já estava preenchido.
                foreach (['cidade_origem', 'cidade_destino'] as $campo) {
                    if ($dados[$campo] !== null) {
                        $trecho->{$campo} = $dados[$campo];
                    }
                }

                $trecho->prazo_horas_normal = $dados['prazo_horas_normal'];
                $trecho->prazo_horas_expresso = $dados['prazo_horas_expresso'];

                if ($trecho->trashed()) {
                    $trecho->restore();
                }

                if ($novo || $trecho->isDirty()) {
                    $trecho->atualizado_por = $usuarioId;
                    $trecho->save();
                    $resultado[$novo ? 'criados' : 'atualizados']++;
                } else {
                    $resultado['inalterados']++;
                }
            }
        });

        return $resultado;
    }

    /**
     * Agrupa as linhas pela chave canônica, descartando as incompletas.
     *
     * @param  array<int, array<string, string|null>>  $linhas
     * @param  array<string, mixed>  $resultado
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function agruparPorChave(array $linhas, array &$resultado): array
    {
        $porChave = [];

        foreach ($linhas as $numero => $linha) {
            $origem = $this->limpar($linha['origem_sap'] ?? null);
            $destino = $this->limpar($linha['destino_sap'] ?? null);

            if ($origem === null || $destino === null) {
                $resultado['linhas_ignoradas']++;

                continue;
            }

            $porChave[TrechoSap::chaveDe($origem, $destino)][] = [
                'linha' => $numero,
                'origem_sap' => $origem,
                'destino_sap' => $destino,
                'cidade_origem' => $this->limpar($linha['cidade_origem'] ?? null),
                'cidade_destino' => $this->limpar($linha['cidade_destino'] ?? null),
                'km_trecho' => $this->numero($linha['km_trecho'] ?? null),
                'prazo_horas_normal' => $this->inteiro($linha['prazo_horas_normal'] ?? null),
                'prazo_horas_expresso' => $this->inteiro($linha['prazo_horas_expresso'] ?? null),
            ];
        }

        return $porChave;
    }

    /**
     * Chaves cujas linhas discordam entre si.
     *
     * Linha repetida com os mesmos números é só redundância da planilha e não
     * atrapalha; o que impede a importação é a mesma rota com quilometragem ou
     * prazo diferentes, porque aí não há como saber qual vale.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $porChave
     * @return array<int, string>
     */
    private function conflitos(array $porChave): array
    {
        $conflitos = [];

        foreach ($porChave as $chave => $linhas) {
            $distintas = collect($linhas)
                ->map(fn (array $l) => [$l['km_trecho'], $l['prazo_horas_normal'], $l['prazo_horas_expresso']])
                ->unique(fn (array $valores) => implode('|', $valores));

            if ($distintas->count() < 2) {
                continue;
            }

            $kms = collect($linhas)->pluck('km_trecho')->filter()->unique()->sort()->values();

            $conflitos[] = sprintf(
                '%s: %d linhas divergentes (linhas %s) — km de %s a %s.',
                $chave,
                count($linhas),
                collect($linhas)->pluck('linha')->take(6)->implode(', ').(count($linhas) > 6 ? '…' : ''),
                $kms->first() !== null ? number_format((float) $kms->first(), 1, ',', '.') : '—',
                $kms->last() !== null ? number_format((float) $kms->last(), 1, ',', '.') : '—',
            );
        }

        return $conflitos;
    }

    private function inteiro(?string $valor): ?int
    {
        $numero = $this->numero($valor);

        return $numero !== null ? (int) round($numero) : null;
    }

    /**
     * Modelo em branco, com o cabeçalho que a importação espera.
     */
    public function gerarModelo(): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'modelo_trechos_sap_').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($caminho);
        $writer->addRow(Row::fromValues(self::CABECALHO_MODELO));
        $writer->addRow(Row::fromValues(['ARM-MACAE', 'PACU', 'Macaé', 'São João da Barra', '164', '72', '60']));
        $writer->addRow(Row::fromValues(['ARM-MACAE', 'BASE VITORIA', 'Macaé', 'Vitória', '381', '120', '108']));
        $writer->close();

        return $caminho;
    }
}
