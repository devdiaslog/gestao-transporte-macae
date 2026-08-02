<?php

namespace Tests\Feature;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusSap;
use App\Models\DemandaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->get(route('itens-entrega.index'))
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
            ->get(route('itens-entrega.index'))
            ->assertOk()
            ->assertSee($vence_amanha->numero_rt)
            ->assertSee($vencido->numero_rt)
            ->assertDontSee($vence_em_10_dias->numero_rt);

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['dias' => 15]))
            ->assertOk()
            ->assertSee($vence_em_10_dias->numero_rt);
    }

    public function test_aba_de_suspensos_separa_a_responsabilidade(): void
    {
        $doCliente = $this->item(['status_sap' => StatusSap::SuspensoExterno]);
        $nosso = $this->item(['status_sap' => StatusSap::SuspensoInterno]);

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('itens-entrega.index', ['aba' => 'suspenso_externo']))
            ->assertOk()
            ->assertSee($doCliente->numero_rt)
            ->assertDontSee($nosso->numero_rt);

        $this->actingAs($usuario)
            ->get(route('itens-entrega.index', ['aba' => 'suspenso_interno']))
            ->assertOk()
            ->assertSee($nosso->numero_rt)
            ->assertDontSee($doCliente->numero_rt);
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
            ->get(route('itens-entrega.index', ['situacao' => 'fora_do_prazo']))
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
            ->get(route('itens-entrega.index', ['doc_unitizacao' => '4803478']))
            ->assertOk()
            ->assertSee($doContentor->numero_rt)
            ->assertDontSee($outro->numero_rt);

        $this->actingAs($usuario)
            ->get(route('itens-entrega.index', ['destino' => 'ARM-RIO']))
            ->assertOk()
            ->assertSee($outro->numero_rt)
            ->assertDontSee($doContentor->numero_rt);
    }

    public function test_filtra_itens_que_sumiram_do_sap(): void
    {
        $sumiu = $this->item(['ausente_no_sap_em' => now()]);
        $presente = $this->item();

        $this->actingAs(User::factory()->create())
            ->get(route('itens-entrega.index', ['ausentes' => 1]))
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
