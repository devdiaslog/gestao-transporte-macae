<?php

namespace Tests\Feature;

use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\TipoDemanda;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\User;
use App\Services\DemandaCalculadora;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaItemEdicaoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create();
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    private function demandaCom(array $itens): Demanda
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => 509999001]);

        foreach ($itens as $i => $item) {
            $demanda->itens()->create(array_merge([
                'numero_rt' => '3260000'.$i,
                'numero_item' => '1',
                'subitem' => (string) $i,
            ], $item));
        }

        // Itens sempre chegam acompanhados de um recálculo, como na importação.
        app(DemandaCalculadora::class)->recalcular($demanda->load('itens'));

        return $demanda->load('itens');
    }

    public function test_agrupa_itens_em_etapas_por_origem_e_destino(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'ARM-RIO'],
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'ARM-RIO'],
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'SEROPEDICA'],
        ]);

        $etapas = $demanda->etapas();

        $this->assertCount(2, $etapas);
        // A etapa com mais itens vem primeiro.
        $this->assertSame('ARM-MACAE → ARM-RIO', $etapas->keys()->first());
        $this->assertCount(2, $etapas->get('ARM-MACAE → ARM-RIO'));
        $this->assertCount(1, $etapas->get('ARM-MACAE → SEROPEDICA'));
    }

    public function test_conta_itens_encerrados_considerando_entregues_e_cancelados(): void
    {
        $demanda = $this->demandaCom([
            ['status_item' => StatusItemDemanda::Entregue],
            ['status_item' => StatusItemDemanda::Cancelado],
            ['status_item' => StatusItemDemanda::Pendente],
        ]);

        $this->assertSame(2, $demanda->itensEncerrados());
        $this->assertSame(3, $demanda->itens->count());
    }

    public function test_editar_item_recalcula_os_campos_derivados_da_demanda(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE', 'status_item' => StatusItemDemanda::Pendente],
        ]);
        $item = $demanda->itens->first();

        $this->assertSame(TipoDemanda::Backload, $demanda->fresh()->tipo_demanda);

        $this->actingAs($this->usuario())
            ->put(route('demanda-itens.update', $item), [
                'numero_rt' => $item->numero_rt,
                'numero_item' => $item->numero_item,
                'subitem' => $item->subitem,
                'local_origem' => 'ARM-MACAE',
                'local_destino' => 'SEROPEDICA',
                'status_item' => StatusItemDemanda::Entregue->value,
                'prazo_item' => now()->addDays(5)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $demanda->refresh()->load('itens');

        // Origem deixou de ser ponto-chave e o item foi entregue.
        $this->assertSame(TipoDemanda::Transferencia, $demanda->tipo_demanda);
        $this->assertSame(StatusDemanda::Finalizado, $demanda->status_demanda);
        $this->assertSame(StatusItemDemanda::Entregue, $demanda->itens->first()->status_item);
    }

    public function test_nao_permite_duplicar_a_identificacao_de_item_na_mesma_demanda(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'A', 'local_destino' => 'B'],
            ['local_origem' => 'C', 'local_destino' => 'D'],
        ]);
        [$primeiro, $segundo] = [$demanda->itens[0], $demanda->itens[1]];

        $this->actingAs($this->usuario())
            ->put(route('demanda-itens.update', $segundo), [
                'numero_rt' => $primeiro->numero_rt,
                'numero_item' => $primeiro->numero_item,
                'subitem' => $primeiro->subitem,
            ])
            ->assertSessionHasErrors('numero_rt');
    }

    public function test_pagina_de_edicao_exibe_as_etapas_e_o_contador(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'ARM-RIO', 'status_item' => StatusItemDemanda::Entregue],
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'ARM-RIO', 'status_item' => StatusItemDemanda::Pendente],
        ]);

        $this->actingAs($this->usuario())
            ->get(route('demandas.edit', $demanda))
            ->assertOk()
            ->assertSee('ARM-MACAE → ARM-RIO')
            ->assertSee('1/2');
    }

    public function test_aplicar_status_em_lote_marca_todos_os_itens_da_etapa(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'ARM-RIO', 'status_item' => StatusItemDemanda::Pendente],
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'ARM-RIO', 'status_item' => StatusItemDemanda::Pendente],
        ]);

        $ids = $demanda->itens->pluck('id')->all();

        $this->actingAs($this->usuario())
            ->put(route('demandas.status-etapa', $demanda), [
                'itens' => $ids,
                'status_item' => StatusItemDemanda::Entregue->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $demanda->refresh()->load('itens');

        $this->assertTrue($demanda->itens->every(fn ($i) => $i->status_item === StatusItemDemanda::Entregue));
        $this->assertSame(StatusDemanda::Finalizado, $demanda->status_demanda);
    }

    public function test_nao_aplica_status_em_itens_de_outra_demanda(): void
    {
        $demanda = $this->demandaCom([['local_origem' => 'A', 'local_destino' => 'B']]);

        $outra = Demanda::factory()->create(['numero_demanda' => 509999002]);
        $itemAlheio = $outra->itens()->create([
            'numero_rt' => '999', 'numero_item' => '1', 'subitem' => '1',
            'status_item' => StatusItemDemanda::Pendente,
        ]);

        $this->actingAs($this->usuario())
            ->put(route('demandas.status-etapa', $demanda), [
                'itens' => [$itemAlheio->id],
                'status_item' => StatusItemDemanda::Entregue->value,
            ])
            ->assertSessionHasErrors('itens.0');

        $this->assertSame(StatusItemDemanda::Pendente, $itemAlheio->fresh()->status_item);
    }

    public function test_remover_item_recalcula_a_demanda(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrador]);
        $admin->permissions()->create(['permission' => UserPermission::Dashboard]);

        $demanda = $this->demandaCom([
            ['local_origem' => 'BMAC', 'local_destino' => 'X', 'status_item' => StatusItemDemanda::Pendente],
            ['local_origem' => 'Y', 'local_destino' => 'Z', 'status_item' => StatusItemDemanda::Entregue],
        ]);
        $itemBackload = $demanda->itens->first();

        $this->assertSame(TipoDemanda::Backload, $demanda->fresh()->tipo_demanda);

        $this->actingAs($admin)
            ->delete(route('demanda-itens.destroy', $itemBackload))
            ->assertRedirect();

        $this->assertSame(1, DemandaItem::where('demanda_id', $demanda->id)->count());

        // Sem o item de origem BMAC, deixa de ser Backload.
        $this->assertSame(TipoDemanda::Transferencia, $demanda->fresh()->tipo_demanda);
    }
}
