<?php

namespace Tests\Feature;

use App\Enums\StatusDemanda;
use App\Enums\TipoDemanda;
use App\Models\Demanda;
use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\SubDivisao;
use App\Models\TipoEquipamento;
use App\Services\DemandaCalculadora;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoEfetivoTest extends TestCase
{
    use RefreshDatabase;

    private function subDivisao(string $nome): SubDivisao
    {
        $divisao = Divisao::create(['nome' => 'Poli Macaé', 'status' => true]);

        return SubDivisao::create(['nome' => $nome, 'divisao_id' => $divisao->id, 'status' => true]);
    }

    private function equipamento(array $attrs = []): Equipamento
    {
        $tipo = TipoEquipamento::create(['nome' => 'Motorizado']);

        return Equipamento::create(array_merge([
            'prefixo' => '1993',
            'placa' => 'ABC1D23',
            'status' => true,
            'tipo_id' => $tipo->id,
        ], $attrs));
    }

    public function test_mapeamento_da_subdivisao_por_prioridade(): void
    {
        $this->assertSame('transferencia', Equipamento::grupoDaSubdivisao('TRANSFERÊNCIA'));
        $this->assertSame('load', Equipamento::grupoDaSubdivisao('LOAD - BACKLOAD - PBG'));
        $this->assertSame('load', Equipamento::grupoDaSubdivisao('LOAD - BACKLOAD - BMAC'));
        $this->assertSame('sem_grupo', Equipamento::grupoDaSubdivisao(null));
        $this->assertSame('sem_grupo', Equipamento::grupoDaSubdivisao('Nenhuma'));
    }

    public function test_grupo_demanda_do_veiculo_vence_a_subdivisao(): void
    {
        $sub = $this->subDivisao('LOAD - BACKLOAD - PBG');
        $veiculo = $this->equipamento(['sub_divisao_id' => $sub->id, 'grupo_demanda' => 'transferencia']);

        // grupo_demanda (sinergia) prevalece sobre a subdivisão de repouso.
        $this->assertSame('transferencia', $veiculo->grupoEfetivo());
    }

    public function test_sem_grupo_demanda_cai_na_subdivisao(): void
    {
        $sub = $this->subDivisao('LOAD - BACKLOAD - PBG');
        $veiculo = $this->equipamento(['sub_divisao_id' => $sub->id]);

        $this->assertSame('load', $veiculo->grupoEfetivo());
    }

    public function test_recalculo_persiste_o_grupo_da_demanda_em_andamento_no_veiculo(): void
    {
        $veiculo = $this->equipamento();

        $demanda = Demanda::factory()->create([
            'numero_demanda' => 509910001,
            'equipamento_id' => $veiculo->id,
            'tipo_demanda' => TipoDemanda::Backload,
            'tipo_demanda_manual' => true,
            'status_demanda' => StatusDemanda::EmAndamento,
            'data_hora_inicio_demanda' => now()->subHour(),
        ]);

        app(DemandaCalculadora::class)->recalcular($demanda->load('itens'));

        $this->assertSame('backload', $veiculo->refresh()->grupo_demanda);
        $this->assertSame('backload', $veiculo->grupoEfetivo());
    }
}
