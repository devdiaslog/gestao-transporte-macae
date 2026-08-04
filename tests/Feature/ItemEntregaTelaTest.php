<?php

namespace Tests\Feature;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusSap;
use App\Enums\TipoDemanda;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\DuracaoRota;
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

    /**
     * O foco da tela é o item recém-liberado, que ainda não tem viagem — é
     * onde falta dar previsão. Os demais status continuam acessíveis pelo
     * filtro.
     */
    public function test_lista_apenas_os_liberados_por_padrao(): void
    {
        $liberado = $this->item();
        $programado = $this->item(['status_sap' => StatusSap::Programado]);
        $atendido = $this->item(['status_sap' => StatusSap::Atendido]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho'))
            ->assertOk()
            ->assertSee($liberado->numero_rt)
            ->assertDontSee($programado->numero_rt)
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

    /**
     * O filtro de status aceita vários de uma vez — 13 e 18 separam de quem é a
     * responsabilidade pela suspensão.
     */
    public function test_filtro_de_status_aceita_varios(): void
    {
        $doCliente = $this->item(['status_sap' => StatusSap::SuspensoExterno]);
        $nosso = $this->item(['status_sap' => StatusSap::SuspensoInterno]);
        $liberado = $this->item();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['status' => ['18']]))
            ->assertOk()
            ->assertSee($doCliente->numero_rt)
            ->assertDontSee($nosso->numero_rt)
            ->assertDontSee($liberado->numero_rt);

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['status' => ['13', '18']]))
            ->assertOk()
            ->assertSee($doCliente->numero_rt)
            ->assertSee($nosso->numero_rt)
            ->assertDontSee($liberado->numero_rt);
    }

    /**
     * Sem escolha, valem os status que o cliente cobra.
     */
    public function test_programado_continua_acessivel_pelo_filtro(): void
    {
        $liberado = $this->item();
        $programado = $this->item(['status_sap' => StatusSap::Programado]);
        $cancelado = $this->item(['status_sap' => StatusSap::Cancelado]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['status' => ['03', '04']]))
            ->assertOk()
            ->assertSee($liberado->numero_rt)
            ->assertSee($programado->numero_rt)
            ->assertDontSee($cancelado->numero_rt);
    }

    public function test_status_invalido_cai_no_padrao(): void
    {
        $liberado = $this->item();
        $atendido = $this->item(['status_sap' => StatusSap::Atendido]);

        $this->actingAs(User::factory()->create())
            // 07 não é filtrável e "xx" não existe.
            ->get(route('itens-entrega.trecho', ['status' => ['07', 'xx']]))
            ->assertOk()
            ->assertSee($liberado->numero_rt)
            ->assertDontSee($atendido->numero_rt);
    }

    /**
     * O operador costuma ter o número da viagem em mãos e querer saber o que
     * ela carrega.
     */
    public function test_busca_pelo_numero_da_viagem(): void
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => 509538496]);
        $daViagem = $this->item(['demanda_id' => $demanda->id]);
        $outro = $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['busca' => '509538496']))
            ->assertOk()
            ->assertSee($daViagem->numero_rt)
            ->assertDontSee($outro->numero_rt);
    }

    public function test_busca_por_rt_carga_e_contentor(): void
    {
        $porRt = $this->item(['numero_rt' => '326999001']);
        $porCarga = $this->item(['descricao_item' => 'SKID DE PERFURACAO']);
        $porContentor = $this->item(['doc_unitizacao_superior' => '4810768']);

        $usuario = User::factory()->create();

        foreach ([['326999001', $porRt], ['SKID DE PERFURACAO', $porCarga], ['4810768', $porContentor]] as [$termo, $esperado]) {
            $this->actingAs($usuario)
                ->get(route('itens-entrega.trecho', ['busca' => $termo]))
                ->assertOk()
                ->assertSee($esperado->numero_rt);
        }
    }

    /**
     * Os recortes que o operador usa para replanejar: o que nunca teve
     * previsão, o que foi prometido e não cumprido, e o que vence logo.
     */
    public function test_filtro_de_previsao_sem_previsao(): void
    {
        $semPrevisao = $this->item();
        $comPrevisao = $this->item();
        $comPrevisao->registrarPrevisao(now()->addDay());

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['previsao' => 'sem_previsao']))
            ->assertOk()
            ->assertSee($semPrevisao->numero_rt)
            ->assertDontSee($comPrevisao->numero_rt);
    }

    public function test_filtro_de_previsao_vencida(): void
    {
        $vencida = $this->item();
        $vencida->registrarPrevisao(now()->subDays(2));

        $futura = $this->item();
        $futura->registrarPrevisao(now()->addDays(2));

        $semPrevisao = $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['previsao' => 'vencida']))
            ->assertOk()
            ->assertSee($vencida->numero_rt)
            ->assertDontSee($futura->numero_rt)
            ->assertDontSee($semPrevisao->numero_rt);
    }

    /**
     * O horizonte é dinâmico: o padrão é o dia seguinte, mas o operador escolhe.
     */
    public function test_filtro_de_previsao_proxima_com_horizonte_dinamico(): void
    {
        $amanha = $this->item();
        $amanha->registrarPrevisao(now()->addDay());

        $emCincoDias = $this->item();
        $emCincoDias->registrarPrevisao(now()->addDays(5));

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['previsao' => 'proxima']))
            ->assertOk()
            ->assertSee($amanha->numero_rt)
            ->assertDontSee($emCincoDias->numero_rt);

        $this->actingAs($usuario)
            ->get(route('itens-entrega.trecho', ['previsao' => 'proxima', 'dias_previsao' => 7]))
            ->assertOk()
            ->assertSee($amanha->numero_rt)
            ->assertSee($emCincoDias->numero_rt);
    }

    /**
     * A tela inicial mostra fluxos, não itens soltos: é olhando a rota que a
     * operação decide a previsão.
     */
    public function test_index_agrupa_por_rota_com_somatorios(): void
    {
        $this->item(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE', 'peso_total' => 1000]);
        $this->item(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAÉ', 'peso_total' => 500]);
        $this->item(['local_origem' => 'PBG', 'local_destino' => 'ARM-MACAE', 'peso_total' => 200]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertSee('Itens por rota')
            ->assertViewHas('trechos', function ($trechos) {
                // As grafias ARM-MACAE e ARM-MACAÉ são o mesmo destino.
                $this->assertCount(2, $trechos);

                $pacu = $trechos->firstWhere('local_origem_norm', 'PACU');
                $this->assertSame('ARM MACAE', $pacu->local_destino_norm);
                $this->assertSame(2, (int) $pacu->total);
                $this->assertSame(2, (int) $pacu->sem_previsao);

                return true;
            });
    }

    /**
     * A rota resume as três situações de previsão, que é o que diz onde agir.
     */
    public function test_index_conta_previsao_no_prazo_e_fora_do_prazo(): void
    {
        $noPrazo = $this->item(['local_origem' => 'PACU']);
        $noPrazo->registrarPrevisao($noPrazo->prazo_item->copy()->subHours(2));

        $atrasado = $this->item(['local_origem' => 'PACU']);
        $atrasado->registrarPrevisao($atrasado->prazo_item->copy()->addDay());

        $this->item(['local_origem' => 'PACU']); // sem previsão

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertViewHas('trechos', function ($trechos) {
                $pacu = $trechos->firstWhere('local_origem_norm', 'PACU');

                $this->assertSame(1, (int) $pacu->no_prazo);
                $this->assertSame(1, (int) $pacu->fora_do_prazo);
                $this->assertSame(1, (int) $pacu->sem_previsao);

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
            // Fora do escopo não vira card: não é gerido por esta tela.
            ->assertViewHas('resumo', fn (array $r) => $r['no_prazo'] === 1
                && $r['fora_do_prazo'] === 1
                && $r['sem_previsao'] === 1
                && ! array_key_exists('fora_escopo', $r)
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
        // O CSV usa o mesmo vocabulário da tela.
        $this->assertStringContainsString('Previsto no prazo', $csv);
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

    /**
     * Prazo renegociado com o cliente vale sobre o do SAP: mostrar "fora do
     * prazo" contra uma data que não vale mais é falso alarme.
     */
    public function test_renegocia_o_prazo_e_o_item_deixa_de_estar_atrasado(): void
    {
        $item = $this->item(['prazo_item' => now()->addDay(), 'prazo_sap' => now()->addDay()]);
        // Previsão depois do prazo original: hoje aparece como fora do prazo.
        $item->registrarPrevisao(now()->addDays(5));
        $this->assertSame('fora_do_prazo', $item->fresh()->situacaoPrevisao());

        $usuario = User::factory()->create();
        $novoPrazo = now()->addDays(7)->startOfMinute();

        $this->actingAs($usuario)
            ->post(route('itens-entrega.prazo'), [
                'itens' => [$item->id],
                'prazo_item' => $novoPrazo->format('Y-m-d\TH:i'),
                'motivo' => 'Acordado com o cliente na reunião de segunda',
            ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();

        $this->assertTrue($item->prazo_item->equalTo($novoPrazo));
        $this->assertSame($usuario->id, $item->prazo_alterado_por);
        $this->assertNotNull($item->prazo_alterado_em);
        $this->assertSame('Acordado com o cliente na reunião de segunda', $item->prazo_motivo);
        $this->assertTrue($item->prazoRenegociado());

        // Com o prazo acordado, a previsão volta a caber.
        $this->assertSame('no_prazo', $item->situacaoPrevisao());
    }

    public function test_renegociacao_de_prazo_exige_motivo(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.prazo'), [
                'itens' => [$item->id],
                'prazo_item' => now()->addDays(3)->format('Y-m-d\TH:i'),
                'motivo' => 'ok',
            ])->assertSessionHasErrors('motivo');
    }

    /**
     * Renegociar prazo é compromisso com o cliente: o Operador não tem essa
     * permissão por padrão.
     */
    public function test_renegociacao_de_prazo_exige_permissao_propria(): void
    {
        $item = $this->item(['prazo_item' => now()->addDay()]);
        $prazoOriginal = $item->prazo_item;

        $this->actingAs(User::factory()->comPerfil('Operador')->create())
            ->post(route('itens-entrega.prazo'), [
                'itens' => [$item->id],
                'prazo_item' => now()->addDays(7)->format('Y-m-d\TH:i'),
                'motivo' => 'Acordado com o cliente',
            ])->assertForbidden();

        $this->assertTrue($item->fresh()->prazo_item->equalTo($prazoOriginal));
    }

    public function test_baixa_o_modelo_de_importacao(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.modelo'))
            ->assertOk()
            ->assertDownload('modelo-importacao-itens-entrega.xlsx');
    }

    public function test_export_respeita_o_status_selecionado(): void
    {
        $emCobranca = $this->item();
        $suspenso = $this->item(['status_sap' => StatusSap::SuspensoExterno]);

        $csv = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.export', ['status' => ['18']]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($suspenso->numero_rt, $csv);
        $this->assertStringNotContainsString($emCobranca->numero_rt, $csv);
    }

    public function test_define_o_tipo_do_item_pela_tela_e_protege_do_sap(): void
    {
        $item = $this->item(['local_origem' => 'BASE VITORIA', 'local_destino' => 'PACU']);

        // Ponto-chave no destino: o tipo já nasce classificado pela rota.
        $this->assertSame(TipoDemanda::Load, $item->tipo_item);

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.tipo'), [
                'itens' => [$item->id],
                'tipo_item' => TipoDemanda::Transferencia->value,
            ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();
        $this->assertSame(TipoDemanda::Transferencia, $item->tipo_item);
        $this->assertTrue($item->tipo_item_manual);

        // Mudar a rota deixa de reclassificar o item.
        $item->update(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE']);
        $this->assertSame(TipoDemanda::Transferencia, $item->refresh()->tipo_item);
    }

    public function test_tipo_em_branco_devolve_o_item_a_rota(): void
    {
        $item = $this->item(['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE']);
        $item->definirTipo(TipoDemanda::Load);

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.tipo'), [
                'itens' => [$item->id],
                'tipo_item' => '',
            ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();
        $this->assertSame(TipoDemanda::Backload, $item->tipo_item);
        $this->assertFalse($item->tipo_item_manual);
    }

    /**
     * A planilha do SAP leva minutos para processar. Sem sinal na tela o
     * usuário nao sabe se o clique valeu, e um segundo clique dispararia outra
     * importacao do mesmo arquivo.
     */
    public function test_telas_de_importacao_sinalizam_o_processamento(): void
    {
        $usuario = User::factory()->create();

        foreach ([route('itens-entrega.index'), route('demandas.index')] as $url) {
            $resposta = $this->actingAs($usuario)->get($url)->assertOk();

            $resposta->assertSee('data-importacao', escape: false);
            $resposta->assertSee('data-importacao-botao', escape: false);
            $resposta->assertSee('Importando…', escape: false);
        }
    }

    /**
     * form.submit() por código não dispara o evento 'submit', entao a tela que
     * envia a planilha ao escolher o arquivo ficaria sem sinal nenhum.
     */
    public function test_tela_que_envia_ao_escolher_o_arquivo_aciona_o_sinal(): void
    {
        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('demandas.index'))
            ->assertOk();

        $resposta->assertSee("iniciarImportacao('form-importar')", escape: false);
        $resposta->assertDontSee("getElementById('form-importar').submit()", escape: false);
    }

    public function test_padrao_da_tela_traz_apenas_os_liberados(): void
    {
        $liberado = $this->item(['status_sap' => StatusSap::Liberado]);
        $programado = $this->item(['status_sap' => StatusSap::Programado]);

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho'))
            ->assertOk();

        $resposta->assertSee($liberado->numero_rt);
        $resposta->assertDontSee($programado->numero_rt);
    }

    public function test_filtro_de_prazo_isola_os_ja_vencidos(): void
    {
        $vencido = $this->item(['prazo_item' => now()->subDays(2)]);
        $futuro = $this->item(['prazo_item' => now()->addDay()]);
        $semPrazo = $this->item(['prazo_item' => null]);

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['dias' => 'vencidos']))
            ->assertOk();

        $resposta->assertSee($vencido->numero_rt);
        $resposta->assertDontSee($futuro->numero_rt);
        // Sem prazo não há como afirmar que venceu.
        $resposta->assertDontSee($semPrazo->numero_rt);
    }

    public function test_marca_item_como_faltoso_com_o_motivo(): void
    {
        $item = $this->item();
        $usuario = User::factory()->create();
        $abertura = now()->subHours(3);

        $this->actingAs($usuario)
            ->post(route('itens-entrega.faltoso'), [
                'itens' => [$item->id],
                'motivo' => 'Solicitante não informou o destino final',
                'faltoso_desde' => $abertura->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('itens-entrega.trecho', ['pendencia' => 'com_pendencia']))
            ->assertSessionHas('success');

        $item->refresh();

        $this->assertSame(StatusSap::Faltoso, $item->status_sap);
        $this->assertSame('Solicitante não informou o destino final', $item->faltoso_motivo);
        $this->assertSame($usuario->id, $item->faltoso_por);
        $this->assertTrue($item->faltoso());
        // A espera corre a partir da abertura informada, não do registro.
        $this->assertSame(
            $abertura->copy()->addHours(DemandaItem::HORAS_DE_ESPERA_FALTOSO)->format('Y-m-d H:i'),
            $item->esperaFaltosoAte()->format('Y-m-d H:i'),
        );
        $this->assertFalse($item->esperaFaltosoVencida());
    }

    public function test_faltoso_sem_data_usa_o_instante_do_registro(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.faltoso'), [
                'itens' => [$item->id],
                'motivo' => 'Falta detalhar os itens',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($item->refresh()->faltoso_desde);
    }

    public function test_faltoso_exige_motivo(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.faltoso'), ['itens' => [$item->id], 'motivo' => ''])
            ->assertSessionHasErrors('motivo');

        $this->assertSame(StatusSap::Liberado, $item->refresh()->status_sap);
    }

    public function test_espera_vence_depois_das_48_horas(): void
    {
        $item = $this->item();
        $item->marcarFaltoso('Aguardando contato', now()->subHours(49), null);

        $this->assertTrue($item->esperaFaltosoVencida());
    }

    public function test_importacao_reconhece_o_codigo_10_do_sap(): void
    {
        $caminho = $this->planilha([
            ['326900010', '1', '1', '10.08.2026', '14:00:00', 'A', 'B', 'C', '10'],
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.importar'), ['arquivo' => $caminho])
            ->assertRedirect();

        $item = DemandaItem::where('numero_rt', '326900010')->firstOrFail();

        $this->assertSame(StatusSap::Faltoso, $item->status_sap);
        // O SAP não transmite o motivo: fica em branco até alguém registrar.
        $this->assertNull($item->faltoso_motivo);
    }

    public function test_filtra_os_itens_faltosos(): void
    {
        $faltoso = $this->item();
        $faltoso->marcarFaltoso('Pendência do solicitante', now(), null);
        $liberado = $this->item();

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['status' => ['10'], 'dias' => 0]))
            ->assertOk();

        $resposta->assertSee($faltoso->numero_rt);
        $resposta->assertDontSee($liberado->numero_rt);
    }

    /**
     * "Prazo em dia" compara o prazo com o relógio: quanto do que está em
     * aberto ainda tem tempo. Não depende de previsão lançada.
     */
    public function test_rota_mostra_o_percentual_de_prazo_em_dia(): void
    {
        // 3 com prazo no futuro, 1 vencido  ->  75%
        foreach (range(1, 3) as $i) {
            $this->item(['prazo_item' => now()->addDays(5)]);
        }
        $this->item(['prazo_item' => now()->subDay()]);
        // Sem prazo: fica de fora do denominador.
        $this->item(['prazo_item' => null]);

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk();

        $resposta->assertSee('75%');
        $resposta->assertSee('1 vencido de 4', escape: false);
    }

    public function test_percentual_nao_depende_de_previsao_lancada(): void
    {
        $this->item(['prazo_item' => now()->addDays(5), 'data_hora_previsao_entrega' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->assertSee('100%');
    }

    public function test_rota_sem_prazo_algum_nao_calcula_o_percentual(): void
    {
        $this->item(['prazo_item' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->assertDontSee('vencido de', escape: false);
    }

    /**
     * A média serve para priorizar: entre rotas com o mesmo número de itens, a
     * de menor média é a que aperta antes. Só entram os itens ainda no prazo.
     */
    public function test_rota_mostra_a_media_de_horas_ate_o_prazo(): void
    {
        // 10h e 20h  ->  média 15h
        $this->item(['prazo_item' => now()->addHours(10)]);
        $this->item(['prazo_item' => now()->addHours(20)]);
        // Vencido: a média dos vencidos seria negativa e não ajuda a priorizar.
        $this->item(['prazo_item' => now()->subHours(30)]);
        // Sem prazo: nada a medir.
        $this->item(['prazo_item' => null]);

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk();

        $resposta->assertSee('15,0 h', escape: false);
        $resposta->assertSee('2 itens no prazo', escape: false);
    }

    public function test_media_acima_de_dois_dias_aparece_em_dias(): void
    {
        $this->item(['prazo_item' => now()->addHours(72)]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->assertSee('3,0 d', escape: false);
    }

    public function test_rota_so_com_vencidos_nao_tem_media(): void
    {
        $this->item(['prazo_item' => now()->subDay()]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->assertDontSee('itens no prazo', escape: false);
    }

    public function test_estimativa_da_rota_e_gravada_e_muda_a_sugestao(): void
    {
        // Prazo em 10h: com o padrão de 24h a rota não cabe.
        $this->item(['prazo_item' => now()->addHours(10)]);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('itens-entrega.index', ['dias' => 0, 'ordenar' => 'sugestao']))
            ->assertOk()
            ->assertSee('não cabe', escape: false);

        $this->actingAs($usuario)
            ->post(route('itens-entrega.duracao'), [
                'local_origem_norm' => 'BASE VITORIA',
                'local_destino_norm' => 'ARM MACAE',
                'horas' => 6,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(6.0, DuracaoRota::firstOrFail()->horas);

        // Com 6h passa a caber.
        $this->actingAs($usuario)
            ->get(route('itens-entrega.index', ['dias' => 0, 'ordenar' => 'sugestao']))
            ->assertOk()
            ->assertDontSee('não cabe', escape: false);
    }

    public function test_duracao_recusa_valor_fora_da_faixa(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('itens-entrega.duracao'), [
                'local_origem_norm' => 'A',
                'local_destino_norm' => 'B',
                'horas' => 0,
            ])
            ->assertSessionHasErrors('horas');

        $this->assertSame(0, DuracaoRota::count());
    }

    /**
     * A rota que não cabe no prazo nem sendo atendida agora é marcada, para o
     * usuário saber que ali a perda já está dada.
     */
    public function test_rota_que_nao_cabe_no_prazo_e_sinalizada(): void
    {
        // Prazo em 2h, mas a rota leva 24h (padrão): impossível.
        $this->item(['prazo_item' => now()->addHours(2)]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0, 'ordenar' => 'sugestao']))
            ->assertOk()
            ->assertSee('não cabe', escape: false);
    }

    public function test_rota_cabe_quando_a_estimativa_permite(): void
    {
        $this->item(['prazo_item' => now()->addHours(10)]);
        DuracaoRota::definir('BASE VITORIA', 'ARM MACAE', 4, null);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0, 'ordenar' => 'sugestao']))
            ->assertOk()
            ->assertSee('itens cabem no prazo nesta ordem', escape: false)
            ->assertDontSee('não cabe', escape: false);
    }

    /**
     * O select define a ordem, e a coluna "Ordem" numera a lista já ordenada —
     * o 1 é sempre a primeira rota pelo critério escolhido.
     */
    public function test_ordenacao_padrao_e_por_numero_de_itens(): void
    {
        // Rota com 1 item.
        $this->item(['local_origem_norm' => 'A', 'local_origem' => 'A', 'local_destino' => 'Z']);
        // Rota com 3 itens.
        foreach (range(1, 3) as $i) {
            $this->item(['local_origem' => 'B', 'local_destino' => 'Z']);
        }

        $html = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->getContent();

        // A rota de 3 itens aparece antes da de 1.
        $this->assertLessThan(mb_strpos($html, '>A<'), mb_strpos($html, '>B<'));
    }

    public function test_select_oferece_os_criterios_de_ordenacao(): void
    {
        $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertSee('Ordenar por')
            ->assertSee('Número de itens')
            ->assertSee('Maior % no prazo')
            ->assertSee('Maior folga até o prazo')
            ->assertSee('Sugestão de atendimento');
    }

    /**
     * A lista serve para escolher o próximo atendimento, então o topo é o que
     * ainda dá para entregar — não o que já está perdido.
     */
    public function test_maior_folga_ate_o_prazo_vem_primeiro(): void
    {
        // Aperta em 2h: pouca chance de dar conta.
        $this->item(['local_origem' => 'APERTADA', 'local_destino' => 'Z', 'prazo_item' => now()->addHours(2)]);
        foreach (range(1, 5) as $i) {
            $this->item(['local_origem' => 'FOLGADA', 'local_destino' => 'Z', 'prazo_item' => now()->addDays(3)]);
        }

        $html = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0, 'ordenar' => 'media_prazo']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(mb_strpos($html, 'APERTADA'), mb_strpos($html, 'FOLGADA'));
    }

    public function test_maior_percentual_no_prazo_vem_primeiro(): void
    {
        // Todos vencidos: nada a salvar.
        $this->item(['local_origem' => 'PERDIDA', 'local_destino' => 'Z', 'prazo_item' => now()->subDays(2)]);
        // Todos em dia.
        $this->item(['local_origem' => 'INTEIRA', 'local_destino' => 'Z', 'prazo_item' => now()->addDays(2)]);

        $html = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0, 'ordenar' => 'prazo_pct']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(mb_strpos($html, 'PERDIDA'), mb_strpos($html, 'INTEIRA'));
    }

    /**
     * Entre rotas do mesmo tamanho, vai na frente a que ainda tem chance.
     */
    public function test_chance_no_prazo_desempata_o_criterio_padrao(): void
    {
        $this->item(['local_origem' => 'VENCIDA', 'local_destino' => 'Z', 'prazo_item' => now()->subDay()]);
        $this->item(['local_origem' => 'EM DIA', 'local_destino' => 'Z', 'prazo_item' => now()->addDays(2)]);

        $html = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(mb_strpos($html, 'VENCIDA'), mb_strpos($html, 'EM DIA'));
    }

    public function test_ordenacao_desconhecida_cai_no_padrao(): void
    {
        $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['ordenar' => 'inexistente']))
            ->assertOk()
            ->assertSee('<option value="itens" selected>Número de itens</option>', escape: false);
    }

    /**
     * A ordem sugerida não é óbvia à primeira vista, então a tela explica o
     * que ela persegue e sob quais premissas — inclusive a de um atendimento
     * por vez, que faz o número ser conservador com frota em campo.
     */
    public function test_sugestao_explica_o_criterio_na_tela(): void
    {
        $this->item(['prazo_item' => now()->addDays(2)]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0, 'ordenar' => 'sugestao']))
            ->assertOk()
            ->assertSee('maior número de itens no prazo', escape: false)
            ->assertSee('um atendimento por vez', escape: false);
    }

    public function test_explicacao_nao_aparece_nas_outras_ordenacoes(): void
    {
        $this->item(['prazo_item' => now()->addDays(2)]);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->assertDontSee('um atendimento por vez', escape: false);
    }

    /**
     * A equipe registra a pendência assim que a identifica; o código 10 só
     * chega na importação seguinte — quando chega. Filtrar por status
     * esconderia exatamente o que acabou de ser registrado.
     */
    public function test_acha_a_pendencia_mesmo_com_o_sap_ainda_em_03(): void
    {
        $item = $this->item();
        $item->marcarFaltoso('Falta informar o destino', now(), null);

        // O SAP reimporta e devolve o item para liberado.
        $item->update(['status_sap' => StatusSap::Liberado]);

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['pendencia' => 'com_pendencia']))
            ->assertOk();

        $resposta->assertSee($item->numero_rt);
        $resposta->assertSee('Falta informar o destino');
    }

    public function test_filtra_apenas_a_espera_vencida(): void
    {
        $dentro = $this->item();
        $dentro->marcarFaltoso('Ainda no prazo de resposta', now()->subHours(10), null);

        $vencida = $this->item();
        $vencida->marcarFaltoso('Solicitante não respondeu', now()->subHours(60), null);

        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.trecho', ['pendencia' => 'espera_vencida']))
            ->assertOk();

        $resposta->assertSee($vencida->numero_rt);
        $resposta->assertDontSee($dentro->numero_rt);
    }

    /**
     * Pendência não é questão de prazo do item: o horizonte D+N não pode
     * escondê-la, ou a equipe conclui que não há pendência aberta.
     */
    public function test_horizonte_de_prazo_nao_esconde_a_pendencia(): void
    {
        $item = $this->item(['prazo_item' => now()->addDays(30)]);
        $item->marcarFaltoso('Pendência de item com prazo distante', now(), null);

        $this->actingAs(User::factory()->create())
            // D+3 é o padrão e deixaria este item de fora.
            ->get(route('itens-entrega.trecho', ['pendencia' => 'com_pendencia', 'dias' => 3]))
            ->assertOk()
            ->assertSee($item->numero_rt);
    }

    public function test_select_de_pendencia_aparece_na_tela(): void
    {
        $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertSee('Com pendência registrada')
            ->assertSee('Espera com o solicitante vencida');
    }

    /**
     * O item marcado deixa o recorte em uso — some da tela se os filtros
     * antigos forem mantidos. A marcação leva à lista de pendências, onde ele
     * está visível.
     */
    public function test_marcar_faltoso_leva_para_a_lista_de_pendencias(): void
    {
        $item = $this->item();

        $resposta = $this->actingAs(User::factory()->create())
            ->from(route('itens-entrega.trecho', ['status' => ['03'], 'dias' => 3, 'busca' => 'algo']))
            ->post(route('itens-entrega.faltoso'), [
                'itens' => [$item->id],
                'motivo' => 'Falta detalhar os itens',
            ])
            ->assertRedirect(route('itens-entrega.trecho', ['pendencia' => 'com_pendencia']));

        // Os filtros que estavam em uso não voltam junto.
        $destino = $resposta->headers->get('Location');
        $this->assertStringNotContainsString('busca=', $destino);
        $this->assertStringNotContainsString('status', $destino);

        $this->actingAs(User::factory()->create())
            ->get($destino)
            ->assertOk()
            ->assertSee($item->numero_rt)
            ->assertSee('Falta detalhar os itens');
    }
}
