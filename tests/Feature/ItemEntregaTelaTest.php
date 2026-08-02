<?php

namespace Tests\Feature;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusSap;
use App\Models\DemandaItem;
use App\Models\User;
use App\Support\ContentorSap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ItemEntregaTelaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $extra
     */
    private function item(array $extra = []): DemandaItem
    {
        static $sequencia = 0;
        $sequencia++;

        return DemandaItem::create(array_merge([
            'numero_rt' => '32600'.str_pad((string) $sequencia, 4, '0', STR_PAD_LEFT),
            'numero_item' => '1',
            'subitem' => '1',
            'status_sap' => StatusSap::Liberado,
            'prazo_item' => now()->addDay()->setTime(23, 59, 59),
            'local_origem' => 'BASE VITORIA',
            'local_destino' => 'ARM-MACAE',
        ], $extra));
    }

    public function test_exige_autenticacao(): void
    {
        $this->get(route('itens-entrega.index'))->assertRedirect(route('login'));
    }

    public function test_exige_permissao_de_ver(): void
    {
        $this->actingAs(User::factory()->semPerfil()->create())
            ->get(route('itens-entrega.index'))
            ->assertForbidden();
    }

    public function test_lista_itens_em_cobranca_por_padrao(): void
    {
        $liberado = $this->item();
        $programado = $this->item(['status_sap' => StatusSap::Programado]);
        $atendido = $this->item(['status_sap' => StatusSap::Atendido]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho'))
            ->assertOk()
            ->assertSee($liberado->numero_rt)
            ->assertSee($programado->numero_rt)
            ->assertDontSee($atendido->numero_rt);
    }

    /**
     * O cliente pede a visão antecipada: por padrão, o que vence em até 3 dias.
     */
    public function test_filtro_dn_limita_o_horizonte_mas_mantem_os_vencidos(): void
    {
        $vence_amanha = $this->item(['prazo_item' => now()->addDay()]);
        $vence_em_10_dias = $this->item(['prazo_item' => now()->addDays(10)]);
        $vencido = $this->item(['prazo_item' => now()->subDays(2)]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho'))
            ->assertOk()
            ->assertSee($vence_amanha->numero_rt)
            ->assertSee($vencido->numero_rt)
            ->assertDontSee($vence_em_10_dias->numero_rt);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['dias' => 15]))
            ->assertOk()
            ->assertSee($vence_em_10_dias->numero_rt);
    }

    public function test_aba_de_suspensos_separa_a_responsabilidade(): void
    {
        $doCliente = $this->item(['status_sap' => StatusSap::SuspensoExterno]);
        $nosso = $this->item(['status_sap' => StatusSap::SuspensoInterno]);

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['aba' => 'suspenso_externo']))
            ->assertOk()
            ->assertSee($doCliente->numero_rt)
            ->assertDontSee($nosso->numero_rt);

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['aba' => 'suspenso_interno']))
            ->assertOk()
            ->assertSee($nosso->numero_rt)
            ->assertDontSee($doCliente->numero_rt);
    }

    /**
     * A tela inicial mostra fluxos, não itens soltos: é olhando o trecho que a
     * operação decide a previsão.
     */
    public function test_index_agrupa_por_trecho_com_somatorios(): void
    {
        $this->item(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE', 'peso_total' => 1000]);
        $this->item(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAÉ', 'peso_total' => 500]);
        $this->item(['local_origem' => 'PBG', 'local_destino' => 'ARM-MACAE', 'peso_total' => 200]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertViewHas('trechos', function ($trechos) {
                // As grafias ARM-MACAE e ARM-MACAÉ são o mesmo destino.
                $this->assertCount(2, $trechos);

                $pacu = $trechos->firstWhere('local_origem_norm', 'PACU');
                $this->assertSame('ARM MACAE', $pacu->local_destino_norm);
                $this->assertSame(2, (int) $pacu->total);
                $this->assertSame(1500.0, (float) $pacu->peso);
                $this->assertSame(2, (int) $pacu->sem_previsao);

                return true;
            });
    }

    /**
     * O operador define a previsão olhando a carga, não só o número da RT.
     */
    public function test_tela_do_trecho_mostra_as_caracteristicas_da_carga(): void
    {
        $item = $this->item([
            'descricao_local_retirada' => 'AL-06 B06R13Q01A',
            'peso_total' => 2408,
            'comprimento' => 3.10,
            'largura' => 3.30,
            'altura' => 3.60,
            'data_hora_liberacao_rt' => '2026-07-03 13:56:46',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho'))
            ->assertOk()
            ->assertSee('AL-06 B06R13Q01A')
            ->assertSee('2.408 kg')
            ->assertSee('3,10 × 3,30 × 3,60 m')
            ->assertSee('03/07/2026 13:56');

        // Área é só comprimento × largura: a altura não ocupa piso.
        $this->assertSame(10.23, $item->area());
        $this->assertSame('3,10 × 3,30 × 3,60', $item->dimensoes());
    }

    /**
     * Dentro de um contentor quem ocupa o piso é o contentor. Somar as caixas
     * de dentro contaria o mesmo espaço várias vezes.
     */
    public function test_area_de_piso_conta_o_contentor_uma_vez_so(): void
    {
        // Três itens no mesmo contentor de 3,0 × 2,4 m.
        $noContentor = collect(range(1, 3))->map(fn ($n) => $this->item([
            'comprimento' => 1.0,
            'largura' => 1.0,
            'doc_unitizacao_superior' => '4810768',
            'descricao_contentor' => 'CISA1010093 Container 3MDry(3,0x2,4x2,4)',
            'comprimento_embalagem' => 3.0,
            'largura_embalagem' => 2.4,
            'altura_embalagem' => 2.4,
        ]));

        $solto = $this->item(['comprimento' => 2.0, 'largura' => 1.5]);

        $this->assertSame(7.2, (float) $noContentor->first()->fresh()->area_embalagem);
        $this->assertSame(7.2, $noContentor->first()->fresh()->areaEfetiva());

        // 7,2 do contentor (uma vez) + 3,0 do item solto.
        $this->assertSame(10.2, ContentorSap::areaDePiso($noContentor->push($solto)->map->fresh()));
    }

    /**
     * O SAP às vezes manda a medida em centímetros ou milímetros. Um item de
     * 2200 × 600 somaria 1.320.000 m² e tornaria o total inutilizável.
     */
    public function test_medida_fora_de_escala_nao_entra_nos_totais(): void
    {
        $normal = $this->item(['local_origem' => 'PACU', 'comprimento' => 2.0, 'largura' => 1.5]);
        $emMilimetros = $this->item(['local_origem' => 'PACU', 'comprimento' => 2200, 'largura' => 600]);

        $this->assertFalse($normal->medidaSuspeita());
        $this->assertTrue($emMilimetros->medidaSuspeita());

        // Só os 3 m² do item plausível entram.
        $this->assertSame(3.0, ContentorSap::areaDePiso([$normal, $emMilimetros]));

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertViewHas('trechos', function ($trechos) {
                $pacu = $trechos->firstWhere('local_origem_norm', 'PACU');
                $this->assertSame(3.0, $pacu->area);
                $this->assertSame(1, (int) $pacu->medidas_suspeitas);

                return true;
            });
    }

    /**
     * O valor original continua visível: o dado do SAP não é corrigido por
     * adivinhação, só sinalizado.
     */
    public function test_item_fora_de_escala_e_sinalizado_na_tela(): void
    {
        $this->item(['comprimento' => 2200, 'largura' => 600, 'altura' => 600]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho'))
            ->assertOk()
            ->assertSee('2.200,00 × 600,00 × 600,00 m')
            ->assertSee('Medida fora de escala', false);
    }

    public function test_area_da_embalagem_vem_das_colunas_do_sap(): void
    {
        $item = $this->item([
            'comprimento_embalagem' => 6.0,
            'largura_embalagem' => 2.4,
            'numero_contentor' => '30112163',
            // Descrição com medidas diferentes: as colunas do SAP têm prioridade.
            'descricao_contentor' => 'CISA0420487 Caixa 1M (1,10x1,10x1,10)',
        ]);

        $this->assertSame(14.4, (float) $item->fresh()->area_embalagem);
    }

    /**
     * Quando o layout não traz as colunas de medida, a descrição serve de
     * reserva — o texto costuma repetir as dimensões.
     */
    public function test_area_da_embalagem_cai_para_a_descricao_quando_falta_coluna(): void
    {
        $item = $this->item([
            'numero_contentor' => '30112164',
            'descricao_contentor' => 'CISA0420487 Caixa 1M (1,10x1,10x1,10)',
        ]);

        $this->assertSame(1.21, (float) $item->fresh()->area_embalagem);
    }

    /**
     * A tela agrupa os itens sob a embalagem que os carrega — é a embalagem
     * que viaja, e o piso que ela ocupa aparece uma vez no cabeçalho do grupo.
     */
    public function test_tela_agrupa_por_embalagem_superior(): void
    {
        $embalagem = [
            'doc_unitizacao_superior' => '4810768',
            'descricao_contentor' => 'CISA1010093 Container 3MDry(3,0x2,4x2,4)',
            'comprimento_embalagem' => 3.0,
            'largura_embalagem' => 2.4,
        ];

        $this->item(array_merge($embalagem, ['peso_total' => 100]));
        $this->item(array_merge($embalagem, ['peso_total' => 250]));
        $solto = $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho'))
            ->assertOk()
            ->assertSee('4810768')
            // Cabeçalho do grupo traz os totais da embalagem.
            ->assertSee('350 kg')
            ->assertSee('7,20 m² de piso')
            ->assertSee($solto->numero_rt)
            ->assertViewHas('embalagens', function ($embalagens) {
                $this->assertCount(1, $embalagens);
                $e = $embalagens->first();
                $this->assertSame(2, $e['itens']);
                $this->assertSame(350.0, $e['peso']);
                $this->assertSame(7.2, $e['area']);

                return true;
            });
    }

    /**
     * O documento de unitização é o que agrupa; o número do contentor só
     * aparece no export de viagem e serve para o item que não passou pela
     * liberação.
     */
    public function test_embalagem_prefere_o_documento_de_unitizacao(): void
    {
        $comDoc = $this->item(['doc_unitizacao_superior' => '4810768', 'numero_contentor' => '30112162']);
        $soContentor = $this->item(['numero_contentor' => '30112163']);
        $solto = $this->item();

        $this->assertSame('4810768', $comDoc->embalagemSuperior());
        $this->assertSame('30112163', $soContentor->embalagemSuperior());
        $this->assertNull($solto->embalagemSuperior());
    }

    /**
     * Embalagem sem medidas conhecidas não pode zerar a área do grupo.
     */
    public function test_embalagem_sem_medidas_vale_o_que_o_item_ocupa(): void
    {
        $item = $this->item([
            'doc_unitizacao_superior' => '4810768',
            'comprimento' => 2.0,
            'largura' => 1.5,
        ]);

        $this->assertNull($item->fresh()->area_embalagem);
        $this->assertSame(3.0, ContentorSap::areaDePiso([$item->fresh()]));
    }

    public function test_filtra_os_itens_de_uma_embalagem(): void
    {
        $dentro = $this->item(['numero_contentor' => '30112162']);
        $fora = $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['contentor' => '30112162']))
            ->assertOk()
            ->assertSee($dentro->numero_rt)
            ->assertDontSee($fora->numero_rt);
    }

    public function test_area_do_trecho_soma_contentor_uma_vez(): void
    {
        foreach (range(1, 3) as $n) {
            $this->item([
                'local_origem' => 'PACU',
                'local_destino' => 'ARM-MACAE',
                'comprimento' => 1.0,
                'largura' => 1.0,
                'numero_contentor' => '30112162',
                'comprimento_embalagem' => 3.0,
                'largura_embalagem' => 2.4,
            ]);
        }
        $this->item(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE', 'comprimento' => 2.0, 'largura' => 1.5]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertViewHas('trechos', function ($trechos) {
                // 7,2 do contentor + 3,0 do solto — não 3 × 1,0 + 3,0.
                $this->assertSame(10.2, $trechos->firstWhere('local_origem_norm', 'PACU')->area);

                return true;
            });
    }

    /**
     * Um contentor é montado para um destino só, então a área dele nunca é
     * contada em mais de um trecho — nem quando o mesmo número aparece em
     * itens de trechos diferentes por erro de cadastro.
     */
    public function test_contentor_nao_duplica_area_entre_trechos(): void
    {
        $contentor = [
            'numero_contentor' => '30112162',
            'comprimento_embalagem' => 3.0,
            'largura_embalagem' => 2.4,
        ];

        $this->item(array_merge($contentor, ['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE']));
        $this->item(array_merge($contentor, ['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE']));

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertViewHas('trechos', function ($trechos) {
                $this->assertCount(1, $trechos);
                // Dois itens, um contentor: 7,2 m² e não 14,4.
                $this->assertSame(7.2, $trechos->first()->area);

                return true;
            });
    }

    public function test_area_e_dimensoes_sao_nulas_sem_medidas(): void
    {
        $item = $this->item(['comprimento' => null, 'largura' => null, 'altura' => null]);

        $this->assertNull($item->area());
        $this->assertNull($item->dimensoes());

        // Sem uma das duas medidas de piso não há área a calcular.
        $item->update(['comprimento' => 3.0]);
        $this->assertNull($item->fresh()->area());
        $this->assertSame('3,00 × ? × ?', $item->fresh()->dimensoes());
    }

    public function test_export_traz_dimensoes_e_area(): void
    {
        $this->item(['comprimento' => 3.10, 'largura' => 3.30, 'altura' => 3.60, 'peso_total' => 2408]);

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.export'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Área (m²)', $csv);
        $this->assertStringContainsString('Comprimento (m)', $csv);
        $this->assertStringContainsString('10.23', $csv);
    }

    public function test_trecho_filtra_pelos_locais_normalizados(): void
    {
        $doTrecho = $this->item(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAÉ']);
        $outro = $this->item(['local_origem' => 'PBG', 'local_destino' => 'ARM-MACAE']);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['origem_norm' => 'PACU', 'destino_norm' => 'ARM MACAE']))
            ->assertOk()
            ->assertSee($doTrecho->numero_rt)
            ->assertDontSee($outro->numero_rt);
    }

    public function test_item_atendido_nao_aparece_em_lugar_nenhum(): void
    {
        $atendido = $this->item(['status_sap' => StatusSap::Atendido]);
        $cancelado = $this->item(['status_sap' => StatusSap::Cancelado]);
        $emCobranca = $this->item();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho'))
            ->assertOk()
            ->assertSee($emCobranca->numero_rt)
            ->assertDontSee($atendido->numero_rt)
            ->assertDontSee($cancelado->numero_rt);

        // Nem por uma aba inventada na URL.
        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['aba' => 'encerrados']))
            ->assertOk()
            ->assertDontSee($atendido->numero_rt);
    }

    public function test_semaforo_conta_cada_situacao(): void
    {
        $noPrazo = $this->item();
        $noPrazo->registrarPrevisao($noPrazo->prazo_item->copy()->subHours(2));

        $atrasado = $this->item();
        $atrasado->registrarPrevisao($atrasado->prazo_item->copy()->addDay());

        $this->item(); // sem previsão
        $foraEscopo = $this->item();
        $foraEscopo->marcarForaDoEscopo('Transporte próprio', User::factory()->create()->id);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            // O item fora do escopo não entra em "sem previsão": ele saiu da
            // fila de trabalho, então cobrar previsão dele seria ruído.
            ->assertViewHas('resumo', fn (array $r) => $r['no_prazo'] === 1
                && $r['fora_do_prazo'] === 1
                && $r['sem_previsao'] === 1
                && $r['fora_escopo'] === 1
                && $r['total'] === 4);
    }

    public function test_filtra_por_situacao(): void
    {
        $atrasado = $this->item();
        $atrasado->registrarPrevisao($atrasado->prazo_item->copy()->addDay());
        $semPrevisao = $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['situacao' => 'fora_do_prazo']))
            ->assertOk()
            ->assertSee($atrasado->numero_rt)
            ->assertDontSee($semPrevisao->numero_rt);
    }

    public function test_filtra_por_contentor_e_por_trecho(): void
    {
        $doContentor = $this->item(['doc_unitizacao_superior' => '4803478']);
        $outro = $this->item(['doc_unitizacao_superior' => '9999999', 'local_destino' => 'ARM-RIO']);

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['doc_unitizacao' => '4803478']))
            ->assertOk()
            ->assertSee($doContentor->numero_rt)
            ->assertDontSee($outro->numero_rt);

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['destino_norm' => 'ARM RIO']))
            ->assertOk()
            ->assertSee($outro->numero_rt)
            ->assertDontSee($doContentor->numero_rt);
    }

    public function test_filtra_itens_que_sumiram_do_sap(): void
    {
        $sumiu = $this->item(['ausente_no_sap_em' => now()]);
        $presente = $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['ausentes' => 1]))
            ->assertOk()
            ->assertSee($sumiu->numero_rt)
            ->assertDontSee($presente->numero_rt);
    }

    public function test_define_previsao_em_lote(): void
    {
        $a = $this->item();
        $b = $this->item();
        $usuario = User::factory()->create();
        $previsao = now()->addDays(2)->startOfMinute();

        $this->actingAs($usuario)
            ->post(route('itens-entrega.previsao'), [
                'itens' => [$a->id, $b->id],
                'data_hora_previsao' => $previsao->format('Y-m-d\TH:i'),
                'motivo' => 'Carreta programada',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        foreach ([$a, $b] as $item) {
            $item->refresh();
            $this->assertTrue($item->data_hora_previsao_entrega->equalTo($previsao));
            $this->assertSame(OrigemPrevisao::Lote, $item->previsaoAtual->origem);
            $this->assertSame($usuario->id, $item->previsaoAtual->definido_por);
            $this->assertSame('Carreta programada', $item->previsaoAtual->motivo);
        }
    }

    public function test_previsao_de_um_item_so_nao_conta_como_lote(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.previsao'), [
                'itens' => [$item->id],
                'data_hora_previsao' => now()->addDay()->format('Y-m-d\TH:i'),
            ])->assertRedirect();

        $this->assertSame(OrigemPrevisao::Manual, $item->fresh()->previsaoAtual->origem);
    }

    public function test_previsao_exige_permissao_propria(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->comPerfil('Visualizador')->create())
            ->post(route('itens-entrega.previsao'), [
                'itens' => [$item->id],
                'data_hora_previsao' => now()->addDay()->format('Y-m-d\TH:i'),
            ])->assertForbidden();

        $this->assertNull($item->fresh()->data_hora_previsao_entrega);
    }

    public function test_previsao_valida_os_campos(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.previsao'), ['itens' => [], 'data_hora_previsao' => ''])
            ->assertSessionHasErrors(['itens', 'data_hora_previsao']);
    }

    public function test_marca_fora_do_escopo_com_justificativa(): void
    {
        $item = $this->item();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('itens-entrega.escopo'), [
                'itens' => [$item->id],
                'fora_escopo' => '1',
                'justificativa' => 'Transporte próprio do cliente',
            ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();
        $this->assertTrue($item->fora_escopo);
        $this->assertSame('Transporte próprio do cliente', $item->fora_escopo_justificativa);
        $this->assertSame($usuario->id, $item->fora_escopo_por);
        $this->assertFalse($item->emCobranca());
    }

    public function test_fora_do_escopo_exige_justificativa(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.escopo'), [
                'itens' => [$item->id],
                'fora_escopo' => '1',
                'justificativa' => 'abc',
            ])->assertSessionHasErrors('justificativa');

        $this->assertFalse($item->fresh()->fora_escopo);
    }

    /**
     * Ao devolver o item ao escopo não faz sentido cobrar justificativa.
     */
    public function test_devolver_ao_escopo_dispensa_justificativa(): void
    {
        $item = $this->item();
        $item->marcarForaDoEscopo('Era do cliente', User::factory()->create()->id);

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.escopo'), [
                'itens' => [$item->id],
                'fora_escopo' => '0',
            ])->assertRedirect()->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertFalse($item->fora_escopo);
        $this->assertNull($item->fora_escopo_justificativa);
    }

    public function test_exporta_os_itens_do_recorte_atual(): void
    {
        $item = $this->item(['descricao_item' => 'SKID P/PROTEÇÃO', 'doc_unitizacao_superior' => '4803478']);
        $item->registrarPrevisao($item->prazo_item->copy()->subHour());

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString($item->numero_rt, $csv);
        $this->assertStringContainsString('SKID P/PROTEÇÃO', $csv);
        $this->assertStringContainsString('4803478', $csv);
        $this->assertStringContainsString('No prazo', $csv);
    }

    /**
     * @param  array<int, array<int, string>>  $linhas
     */
    private function planilha(array $linhas): UploadedFile
    {
        $caminho = tempnam(sys_get_temp_dir(), 'itens_tela_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($caminho);
        $writer->addRow(Row::fromValues([
            'Nota', 'Item', 'Subitem', 'Data Prazo', 'Hora Prazo', 'Origem', 'Destino', 'Descrição da Carga', 'Status',
        ]));
        foreach ($linhas as $linha) {
            $writer->addRow(Row::fromValues($linha));
        }
        $writer->close();

        return new UploadedFile($caminho, 'itens.xlsx', null, null, true);
    }

    public function test_importa_planilha_pela_tela(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.importar'), [
                'arquivo' => $this->planilha([
                    ['326900001', '1', '1', '10.08.2026', '14:00:00', 'BASE VITORIA', 'ARM-MACAE', 'Carga A', '03'],
                    ['326900002', '1', '1', '11.08.2026', '00:00:00', 'BASE VITORIA', 'ARM-MACAE', 'Carga B', '04'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, DemandaItem::count());
        $this->assertSame(StatusSap::Programado, DemandaItem::where('numero_rt', '326900002')->firstOrFail()->status_sap);
        // Hora zerada: o limite é o fim do dia anterior.
        $this->assertSame(
            '10/08/2026 23:59:59',
            DemandaItem::where('numero_rt', '326900002')->firstOrFail()->prazo_item->format('d/m/Y H:i:s')
        );
    }

    /**
     * Marcar ausentes só é seguro quando a planilha é a carga completa; por isso
     * é uma escolha explícita, desligada por padrão.
     */
    public function test_importacao_parcial_nao_marca_ausentes_por_padrao(): void
    {
        $existente = $this->item();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('itens-entrega.importar'), [
                'arquivo' => $this->planilha([
                    ['326900001', '1', '1', '10.08.2026', '14:00:00', 'BASE VITORIA', 'ARM-MACAE', 'Carga A', '03'],
                ]),
            ])->assertRedirect();

        $this->assertNull($existente->fresh()->ausente_no_sap_em);

        $this->actingAs($usuario)
            ->post(route('itens-entrega.importar'), [
                'arquivo' => $this->planilha([
                    ['326900001', '1', '1', '10.08.2026', '14:00:00', 'BASE VITORIA', 'ARM-MACAE', 'Carga A', '03'],
                ]),
                'marcar_ausentes' => '1',
            ])->assertRedirect();

        $this->assertNotNull($existente->fresh()->ausente_no_sap_em);
    }

    public function test_importacao_exige_permissao_propria(): void
    {
        $this->actingAs(User::factory()->comPerfil('Visualizador')->create())
            ->post(route('itens-entrega.importar'), [
                'arquivo' => $this->planilha([['326900001', '1', '1', '10.08.2026', '14:00:00', 'A', 'B', 'C', '03']]),
            ])->assertForbidden();

        $this->assertSame(0, DemandaItem::count());
    }

    public function test_importacao_recusa_arquivo_invalido(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.importar'), [
                'arquivo' => UploadedFile::fake()->create('lista.pdf', 10, 'application/pdf'),
            ])->assertSessionHasErrors('arquivo');
    }

    /**
     * A normalização só resolve grafia. Quando o SAP manda o local errado, quem
     * corrige é a operação — e a correção não pode ser desfeita na importação
     * seguinte.
     */
    public function test_ajusta_a_rota_e_protege_do_sap(): void
    {
        $item = $this->item(['local_origem' => 'BASE VITORIA', 'local_destino' => 'ARM-MACAE (SUCATA)']);

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.rota'), [
                'itens' => [$item->id],
                'local_destino' => 'ARM-MACAE',
            ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();
        $this->assertSame('ARM-MACAE', $item->local_destino);
        $this->assertSame('ARM MACAE', $item->local_destino_norm);
        $this->assertTrue($item->campoEditadoPeloOperador('local_destino'));
        // A origem não foi informada, então continua como estava e livre.
        $this->assertSame('BASE VITORIA', $item->local_origem);
        $this->assertFalse($item->campoEditadoPeloOperador('local_origem'));
    }

    public function test_ajuste_de_rota_exige_origem_ou_destino(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.rota'), ['itens' => [$item->id]])
            ->assertSessionHasErrors(['local_origem', 'local_destino']);
    }

    public function test_ajuste_de_rota_em_lote(): void
    {
        $a = $this->item(['local_destino' => 'ARM MACAÉ']);
        $b = $this->item(['local_destino' => 'ARM-MACAE (AL-17)']);

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.rota'), [
                'itens' => [$a->id, $b->id],
                'local_origem' => 'PACU',
                'local_destino' => 'ARM-MACAE',
            ])->assertRedirect();

        foreach ([$a, $b] as $item) {
            $item->refresh();
            $this->assertSame('PACU', $item->local_origem);
            $this->assertSame('ARM MACAE', $item->local_destino_norm);
        }
    }

    public function test_baixa_o_modelo_de_importacao(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.modelo'))
            ->assertOk()
            ->assertDownload('modelo-importacao-itens-entrega.xlsx');
    }

    public function test_export_respeita_a_aba_selecionada(): void
    {
        $emCobranca = $this->item();
        $suspenso = $this->item(['status_sap' => StatusSap::SuspensoExterno]);

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.export', ['aba' => 'suspenso_externo']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($suspenso->numero_rt, $csv);
        $this->assertStringNotContainsString($emCobranca->numero_rt, $csv);
    }
}
