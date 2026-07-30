<?php

namespace Tests\Feature;

use App\Models\Demanda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_medicao_pelo_crud(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'administrador']))
            ->post(route('medicoes.store'), [
                'nome_medicao' => 'Medição Julho/2026',
                'data_inicio' => '2026-07-01',
                'data_fim' => '2026-07-31',
            ])
            ->assertRedirect(route('medicoes.index'));

        $this->assertDatabaseHas('medicoes', ['nome_medicao' => 'Medição Julho/2026']);
    }

    public function test_data_fim_nao_pode_ser_anterior_ao_inicio(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'administrador']))
            ->post(route('medicoes.store'), [
                'nome_medicao' => 'Inválida',
                'data_inicio' => '2026-07-31',
                'data_fim' => '2026-07-01',
            ])
            ->assertSessionHasErrors('data_fim');
    }

    public function test_data_referencia_usa_criacao_sap_quando_existe(): void
    {
        $comSap = new Demanda([
            'data_hora_criacao_sap' => now()->parse('2026-07-10 08:00'),
        ]);
        $comSap->created_at = now()->parse('2026-07-15 09:00');

        $this->assertSame('10/07/2026', $comSap->dataReferencia()->format('d/m/Y'));

        $semSap = new Demanda;
        $semSap->created_at = now()->parse('2026-07-15 09:00');

        $this->assertSame('15/07/2026', $semSap->dataReferencia()->format('d/m/Y'));
    }
}
