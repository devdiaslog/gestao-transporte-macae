<?php

namespace App\Support;

/**
 * Em que ordem atender as rotas para entregar o maior número de itens no prazo.
 *
 * O ponto de partida é o EDD (prazo mais próximo primeiro), que é a ordem certa
 * para executar um conjunto já escolhido. O que o EDD sozinho não resolve é
 * *quais* rotas escolher: atender primeiro uma rota de 1 item porque ela vence
 * antes pode inviabilizar outra de 9 itens que vence logo depois — e o objetivo
 * é o número de itens entregues, não o de rotas.
 *
 * Por isso a seleção é feita por ganho: a cada passo entra a rota que mais
 * aumenta o total de itens no prazo, e o conjunto escolhido é sempre executado
 * em EDD. Uma rota vale conforme o instante em que termina, porque carrega
 * itens com prazos diferentes — chegar mais tarde perde os que vencem antes e
 * ainda salva os demais.
 */
class SequenciadorRotas
{
    /**
     * @param  array<int, array{chave: string, prazos: array<int, float>, duracao: float}>  $rotas
     *                                                                                              prazos: horas de agora até o prazo de cada item ainda em dia
     *                                                                                              duracao: horas estimadas de atendimento
     * @return array{sequencia: array<int, string>, fora: array<int, string>, itens_no_prazo: int, itens_perdidos: int}
     */
    public static function planejar(array $rotas): array
    {
        $escolhidas = [];
        $totalAtual = 0;

        while (true) {
            $melhor = null;
            $melhorTotal = $totalAtual;

            foreach ($rotas as $i => $candidata) {
                if (isset($escolhidas[$i])) {
                    continue;
                }

                $total = self::itensNoPrazo(self::emOrdem($escolhidas + [$i => $candidata]));

                if ($total > $melhorTotal) {
                    $melhor = $i;
                    $melhorTotal = $total;
                }
            }

            // Nenhuma rota restante acrescenta uma entrega sequer.
            if ($melhor === null) {
                break;
            }

            $escolhidas[$melhor] = $rotas[$melhor];
            $totalAtual = $melhorTotal;
        }

        $sequencia = array_column(self::emOrdem($escolhidas), 'chave');
        $total = array_sum(array_map(fn (array $r) => count($r['prazos']), $rotas));

        return [
            'sequencia' => $sequencia,
            'fora' => array_values(array_diff(array_column($rotas, 'chave'), $sequencia)),
            'itens_no_prazo' => $totalAtual,
            'itens_perdidos' => $total - $totalAtual,
        ];
    }

    /**
     * Executa na ordem do prazo mais próximo: para um conjunto já escolhido,
     * nenhuma outra ordem entrega mais.
     *
     * @param  array<int, array{prazos: array<int, float>, duracao: float}>  $rotas
     * @return array<int, array{prazos: array<int, float>, duracao: float}>
     */
    private static function emOrdem(array $rotas): array
    {
        $ordenadas = array_values($rotas);
        usort($ordenadas, fn (array $a, array $b) => self::primeiroPrazo($a) <=> self::primeiroPrazo($b));

        return $ordenadas;
    }

    /**
     * Itens entregues no prazo quando as rotas são executadas nesta ordem.
     *
     * @param  array<int, array{prazos: array<int, float>, duracao: float}>  $rotas
     */
    private static function itensNoPrazo(array $rotas): int
    {
        $decorrido = 0.0;
        $total = 0;

        foreach ($rotas as $rota) {
            $decorrido += $rota['duracao'];
            $total += count(array_filter($rota['prazos'], fn (float $p) => $p >= $decorrido));
        }

        return $total;
    }

    /**
     * @param  array{prazos: array<int, float>}  $rota
     */
    private static function primeiroPrazo(array $rota): float
    {
        return $rota['prazos'] === [] ? PHP_FLOAT_MAX : min($rota['prazos']);
    }
}
