<?php

namespace Tests\Feature;

use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\TrechoSap;
use App\Models\User;
use App\Services\CalculadoraPrazoTrecho;
use App\Services\DemandaCalculadora;
use App\Services\ImportadorItensLiberados;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O prazo do item passa a vir da rota que ele percorre, contado da liberação
 * da RT — é quando o cliente libera o material e o relógio corre para nós.
 */
class PrazoPorTrechoTest extends TestCase
{
    use RefreshDatabase;

    private function item(array $extra = []): DemandaItem
    {
        static $sequencia = 0;
        $sequencia++;

        return DemandaItem::create(array_merge([
            'numero_rt' => '32690'.str_pad((string) $sequencia, 4, '0', STR_PAD_LEFT),
            'numero_item' => '1',
            'subitem' => '1',
            'local_origem' => 'ARM-MACAE',
            'local_destino' => 'PACU',
            'data_hora_liberacao_rt' => '2026-08-01 08:00:00',
            'status_sap' => '03',
        ], $extra));
    }

    private function trecho(array $extra = []): TrechoSap
    {
        return TrechoSap::create(array_merge([
            'origem_sap' => 'ARM-MACAE',
            'destino_sap' => 'PACU',
            'km_trecho' => 164,
            'prazo_horas_normal' => 72,
            'prazo_horas_expresso' => 60,
            'prazo_padrao' => 'normal',
        ], $extra));
    }

    public function test_prazo_conta_da_liberacao_da_rt(): void
    {
        $this->trecho();
        $item = $this->item();

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $item->refresh();

        // 01/08 08:00 + 72h = 04/08 08:00
        $this->assertSame('2026-08-04 08:00:00', $item->prazo_item->format('Y-m-d H:i:s'));
        $this->assertTrue($item->prazoVeioDoTrecho());
    }

    public function test_prazo_expresso_quando_o_trecho_assim_define(): void
    {
        $this->trecho(['prazo_padrao' => 'expresso']);
        $item = $this->item();

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]]);

        // 60h em vez de 72h
        $this->assertSame('2026-08-03 20:00:00', $item->refresh()->prazo_item->format('Y-m-d H:i:s'));
    }

    /**
     * Rota que ninguém cadastrou não recebe prazo inventado: o item fica como
     * está e a tela diz o que falta.
     */
    public function test_rota_nao_cadastrada_nao_define_prazo(): void
    {
        $item = $this->item(['prazo_item' => null]);

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertNull($item->refresh()->prazo_item);
        $this->assertFalse($item->prazoVeioDoTrecho());
    }

    public function test_item_sem_liberacao_nao_recebe_prazo(): void
    {
        $this->trecho();
        $item = $this->item(['data_hora_liberacao_rt' => null, 'prazo_item' => null]);

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]]);

        $this->assertNull($item->refresh()->prazo_item);
    }

    /**
     * Prazo renegociado é decisão da operação e não é desfeito pelo recálculo.
     */
    public function test_recalculo_respeita_o_prazo_renegociado(): void
    {
        $this->trecho();
        $usuario = User::factory()->comPerfil('Supervisor')->create();

        $item = $this->item();
        $item->renegociarPrazo(now()->addDays(30), $usuario->id, 'Cliente pediu adiamento');
        $prazoNegociado = $item->fresh()->prazo_item;

        $this->actingAs($usuario)
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]])
            ->assertSessionHas('warning');

        $this->assertSame(
            $prazoNegociado->format('Y-m-d H:i'),
            $item->refresh()->prazo_item->format('Y-m-d H:i'),
        );
    }

    public function test_trecho_sem_prazo_cadastrado_nao_calcula(): void
    {
        $this->trecho(['prazo_horas_normal' => null, 'prazo_horas_expresso' => null]);
        $item = $this->item(['prazo_item' => null]);

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]]);

        $this->assertNull($item->refresh()->prazo_item);
    }

    /**
     * A rota do item casa com o trecho pela forma canônica: grafias diferentes
     * do mesmo lugar encontram o mesmo prazo.
     */
    public function test_grafia_diferente_encontra_o_mesmo_trecho(): void
    {
        $this->trecho(['origem_sap' => 'ARM MACAÉ', 'destino_sap' => 'PACU']);
        $item = $this->item(['local_origem' => 'ARM-MACAE']);

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]]);

        $this->assertNotNull($item->refresh()->prazo_item);
    }

    public function test_lote_relata_o_que_nao_foi_calculado(): void
    {
        $this->trecho();

        $ok = $this->item();
        $semTrecho = $this->item(['local_destino' => 'LUGAR DESCONHECIDO']);
        $semLiberacao = $this->item(['data_hora_liberacao_rt' => null]);

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), [
                'itens' => [$ok->id, $semTrecho->id, $semLiberacao->id],
            ])
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $this->assertTrue($ok->refresh()->prazoVeioDoTrecho());
        $this->assertFalse($semTrecho->refresh()->prazoVeioDoTrecho());
        $this->assertFalse($semLiberacao->refresh()->prazoVeioDoTrecho());
    }

    public function test_exige_permissao_de_prazo(): void
    {
        $this->trecho();
        $item = $this->item();

        // Operador não tem itens-entrega.prazo.
        $this->actingAs(User::factory()->comPerfil('Operador')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]])
            ->assertForbidden();
    }

    public function test_rota_sem_trecho_e_sinalizada_na_listagem(): void
    {
        $this->item(['local_destino' => 'LUGAR SEM PRAZO']);

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->assertSee('sem prazo cadastrado');
    }

    public function test_rota_cadastrada_nao_e_sinalizada(): void
    {
        $this->trecho();
        $this->item();

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->get(route('itens-entrega.index', ['dias' => 0]))
            ->assertOk()
            ->assertDontSee('sem prazo cadastrado');
    }

    /**
     * O prazo da demanda é o menor entre os itens dela, então recalcular os
     * itens move a demanda junto.
     */
    public function test_prazo_da_demanda_acompanha_os_itens(): void
    {
        $this->trecho();

        $demanda = Demanda::factory()->create(['numero_demanda' => 509111222]);
        $item = $this->item(['demanda_id' => $demanda->id]);

        $this->actingAs(User::factory()->comPerfil('Supervisor')->create())
            ->post(route('itens-entrega.recalcular-prazo'), ['itens' => [$item->id]]);

        app(DemandaCalculadora::class)->recalcular($demanda->load('itens'));

        $this->assertSame(
            $item->refresh()->prazo_item->format('Y-m-d H:i'),
            $demanda->refresh()->prazo_demanda->format('Y-m-d H:i'),
        );
    }

    public function test_motivos_tem_rotulo_legivel(): void
    {
        $this->assertSame(
            'rota não cadastrada em Trechos SAP',
            CalculadoraPrazoTrecho::rotuloDoMotivo(CalculadoraPrazoTrecho::SEM_TRECHO),
        );
    }

    /**
     * A importação descobre rotas que ninguém cadastrou. Criar o esqueleto na
     * hora transforma isso numa lista de pendências visível em Cadastros, em
     * vez de a equipe descobrir rota a rota ao tentar calcular prazo.
     */
    public function test_importacao_cadastra_a_rota_que_faltava(): void
    {
        $this->assertSame(0, TrechoSap::count());

        app(ImportadorItensLiberados::class)->importarLinhas([
            ['numero_rt' => '326000111', 'numero_item' => '1', 'local_origem' => 'ARM-MACAE', 'local_destino' => 'PACU'],
            ['numero_rt' => '326000222', 'numero_item' => '1', 'local_origem' => 'BASE VITORIA', 'local_destino' => 'ARM-MACAE'],
            // Mesma rota da primeira, com outra grafia: não vira um segundo trecho.
            ['numero_rt' => '326000333', 'numero_item' => '1', 'local_origem' => 'ARM MACAÉ', 'local_destino' => 'PACU'],
        ], null, false);

        $this->assertSame(2, TrechoSap::count());

        $trecho = TrechoSap::where('chave_origem_destino', 'ARM MACAE > PACU')->firstOrFail();

        // O que depende de decisão humana fica em branco.
        $this->assertNull($trecho->km_trecho);
        $this->assertNull($trecho->prazo_horas_normal);
        $this->assertTrue($trecho->incompleto());
    }

    public function test_rota_ja_cadastrada_nao_e_recriada(): void
    {
        $this->trecho();

        app(ImportadorItensLiberados::class)->importarLinhas([
            ['numero_rt' => '326000444', 'numero_item' => '1', 'local_origem' => 'ARM-MACAE', 'local_destino' => 'PACU'],
        ], null, false);

        $this->assertSame(1, TrechoSap::count());
        // O prazo que já estava lá continua intacto.
        $this->assertSame(72, TrechoSap::firstOrFail()->prazo_horas_normal);
    }

    public function test_trecho_incompleto_e_sinalizado_no_cadastro(): void
    {
        TrechoSap::create(['origem_sap' => 'ARM-MACAE', 'destino_sap' => 'PACU', 'prazo_padrao' => 'normal']);

        $this->actingAs(User::factory()->comPerfil('Administrador')->create())
            ->get(route('trechos-sap.index'))
            ->assertOk()
            ->assertSee('a preencher');
    }

    public function test_trecho_completo_nao_e_sinalizado(): void
    {
        $this->trecho();

        $this->actingAs(User::factory()->comPerfil('Administrador')->create())
            ->get(route('trechos-sap.index'))
            ->assertOk()
            ->assertDontSee('a preencher');
    }
}
