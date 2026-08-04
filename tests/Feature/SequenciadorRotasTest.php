<?php

namespace Tests\Feature;

use App\Support\SequenciadorRotas;
use Tests\TestCase;

class SequenciadorRotasTest extends TestCase
{
    /**
     * @param  array<int, array{chave: string, prazos: array<int, float>, duracao: float}>  $rotas
     */
    private function planejar(array $rotas): array
    {
        return SequenciadorRotas::planejar($rotas);
    }

    /**
     * O caso que motivou tudo: 1 item vencendo em 24h contra 2 vencendo em 6h.
     * Não é escolher entre perder 2 ou perder 1 — atendendo os 2 primeiro,
     * ainda sobra tempo para o outro.
     */
    public function test_atende_os_dois_grupos_quando_a_ordem_permite(): void
    {
        $plano = $this->planejar([
            ['chave' => 'A', 'prazos' => [24.0], 'duracao' => 4.0],
            ['chave' => 'B', 'prazos' => [6.0, 6.0], 'duracao' => 4.0],
        ]);

        $this->assertSame(['B', 'A'], $plano['sequencia']);
        $this->assertSame(3, $plano['itens_no_prazo']);
        $this->assertSame(0, $plano['itens_perdidos']);
    }

    /**
     * Uma rota longa e duas curtas com prazos apertados: as três não cabem, e
     * o máximo possível é entregar dois itens. Mais de uma seleção alcança
     * esse máximo, então o que se cobra é o total — que é o objetivo.
     */
    public function test_deixa_de_fora_o_que_nao_cabe_e_entrega_o_maximo(): void
    {
        $plano = $this->planejar([
            ['chave' => 'longa', 'prazos' => [10.0], 'duracao' => 9.0],
            ['chave' => 'curta1', 'prazos' => [11.0], 'duracao' => 2.0],
            ['chave' => 'curta2', 'prazos' => [12.0], 'duracao' => 2.0],
        ]);

        $this->assertSame(2, $plano['itens_no_prazo']);
        $this->assertSame(1, $plano['itens_perdidos']);
        $this->assertCount(2, $plano['sequencia']);
        $this->assertCount(1, $plano['fora']);
    }

    /**
     * A rota de muitos itens não pode ser sacrificada por uma de poucos só
     * porque a pequena vence antes — o objetivo é o número de entregas.
     */
    public function test_prefere_o_conjunto_que_entrega_mais_itens(): void
    {
        $plano = $this->planejar([
            ['chave' => 'um_item', 'prazos' => [5.0], 'duracao' => 5.0],
            ['chave' => 'dez_itens', 'prazos' => array_fill(0, 10, 6.0), 'duracao' => 5.0],
        ]);

        $this->assertSame(['dez_itens'], $plano['sequencia']);
        $this->assertSame(10, $plano['itens_no_prazo']);
    }

    /**
     * Entre durações iguais, sai a que carrega menos itens: o objetivo é o
     * número de entregas no prazo, não o número de viagens.
     */
    public function test_empate_de_duracao_sacrifica_a_rota_com_menos_itens(): void
    {
        $plano = $this->planejar([
            ['chave' => 'poucos', 'prazos' => [5.0], 'duracao' => 5.0],
            ['chave' => 'muitos', 'prazos' => array_fill(0, 9, 6.0), 'duracao' => 5.0],
        ]);

        $this->assertSame(['muitos'], $plano['sequencia']);
        $this->assertSame(9, $plano['itens_no_prazo']);
        $this->assertSame(1, $plano['itens_perdidos']);
    }

    public function test_tudo_cabe_quando_ha_folga(): void
    {
        $plano = $this->planejar([
            ['chave' => 'A', 'prazos' => [100.0, 100.0, 100.0], 'duracao' => 4.0],
            ['chave' => 'B', 'prazos' => [200.0, 200.0], 'duracao' => 4.0],
        ]);

        $this->assertSame(['A', 'B'], $plano['sequencia']);
        $this->assertSame(5, $plano['itens_no_prazo']);
        $this->assertSame(0, $plano['itens_perdidos']);
    }

    /**
     * Rota cujo prazo já venceu não cabe em ordem alguma: entra com prazo
     * negativo e é descartada, sem impedir as demais.
     */
    public function test_rota_ja_vencida_nao_atrapalha_as_demais(): void
    {
        $plano = $this->planejar([
            ['chave' => 'vencida', 'prazos' => array_fill(0, 5, -30.0), 'duracao' => 4.0],
            ['chave' => 'viavel', 'prazos' => [20.0, 20.0], 'duracao' => 4.0],
        ]);

        $this->assertSame(['viavel'], $plano['sequencia']);
        $this->assertSame(2, $plano['itens_no_prazo']);
        $this->assertSame(5, $plano['itens_perdidos']);
    }

    public function test_sem_rotas_devolve_plano_vazio(): void
    {
        $plano = $this->planejar([]);

        $this->assertSame([], $plano['sequencia']);
        $this->assertSame(0, $plano['itens_no_prazo']);
    }

    /**
     * A rota carrega itens com prazos diferentes: concluí-la mais tarde perde
     * os que vencem antes, mas salva os demais. Tratá-la como tudo ou nada
     * descartaria itens perfeitamente recuperáveis.
     */
    public function test_conta_apenas_os_itens_que_a_conclusao_alcanca(): void
    {
        $plano = $this->planejar([
            // Leva 10h: o item de 5h se perde, os de 20h e 30h se salvam.
            ['chave' => 'mista', 'prazos' => [5.0, 20.0, 30.0], 'duracao' => 10.0],
        ]);

        $this->assertSame(['mista'], $plano['sequencia']);
        $this->assertSame(2, $plano['itens_no_prazo']);
        $this->assertSame(1, $plano['itens_perdidos']);
    }

    public function test_rota_que_nao_salva_ninguem_nao_consome_tempo(): void
    {
        $plano = $this->planejar([
            // Chegaria em 8h, tarde para o único item dela.
            ['chave' => 'inutil', 'prazos' => [2.0], 'duracao' => 8.0],
            ['chave' => 'viavel', 'prazos' => [20.0, 20.0], 'duracao' => 8.0],
        ]);

        $this->assertSame(['viavel'], $plano['sequencia']);
        $this->assertSame(2, $plano['itens_no_prazo']);
    }
}
