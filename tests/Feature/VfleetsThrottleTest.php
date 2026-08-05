<?php

namespace Tests\Feature;

use App\Models\PosicaoVeiculo;
use App\Models\User;
use App\Services\VfleetsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A tela do Mapa Geral sincroniza ao abrir. Sem limite entre as chamadas, cada
 * usuário que entra dispara uma; a API responde 429, a sincronização inteira
 * falha e nenhum veículo é atualizado — em três horas todos aparecem como "sem
 * sinal", que foi o incidente de 04/08/2026.
 */
class VfleetsThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function posicao(string $placa, string $sincronizadaEm): PosicaoVeiculo
    {
        return PosicaoVeiculo::create([
            'license_plate' => $placa,
            'position_at' => now(),
            'synced_at' => $sincronizadaEm,
        ]);
    }

    public function test_nao_chama_a_api_quando_a_ultima_sincronizacao_e_recente(): void
    {
        Http::fake();
        $this->posicao('ABC1D23', now()->subSeconds(30)->toDateTimeString());

        $resposta = $this->actingAs(User::factory()->create())
            ->postJson(route('control-tower.sincronizar-posicoes'))
            ->assertOk();

        $resposta->assertJson(['ok' => true, 'total' => 0, 'reaproveitado' => true]);

        Http::assertNothingSent();
    }

    public function test_chama_a_api_quando_o_intervalo_ja_passou(): void
    {
        config(['services.vfleets.intervalo_sincronizacao' => 120]);
        $this->posicao('ABC1D23', now()->subMinutes(10)->toDateTimeString());

        Http::fake([
            '*token*' => Http::response(['access_token' => 'x'], 200),
            '*positions*' => Http::response([
                ['licensePlate' => 'ABC1D23', 'speed' => 0, 'dateTime' => now()->toIso8601String()],
            ], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('control-tower.sincronizar-posicoes'))
            ->assertOk()
            ->assertJson(['ok' => true, 'total' => 1]);
    }

    public function test_primeira_sincronizacao_nao_e_bloqueada(): void
    {
        Http::fake([
            '*token*' => Http::response(['access_token' => 'x'], 200),
            '*positions*' => Http::response([], 200),
        ]);

        // Banco sem nenhuma posição: não há o que reaproveitar.
        $this->actingAs(User::factory()->create())
            ->postJson(route('control-tower.sincronizar-posicoes'))
            ->assertOk()
            ->assertJsonMissing(['reaproveitado' => true]);
    }

    public function test_idade_da_ultima_sincronizacao(): void
    {
        $servico = app(VfleetsService::class);

        $this->assertNull($servico->segundosDesdeUltimaSincronizacao());

        $this->posicao('ABC1D23', now()->subSeconds(90)->toDateTimeString());

        $this->assertEqualsWithDelta(90, $servico->segundosDesdeUltimaSincronizacao(), 2);
    }

    /**
     * O intervalo é configurável: a operação pode afrouxar ou apertar conforme
     * o limite que a API impuser.
     */
    public function test_intervalo_respeita_a_configuracao(): void
    {
        config(['services.vfleets.intervalo_sincronizacao' => 600]);
        $this->posicao('ABC1D23', now()->subMinutes(5)->toDateTimeString());

        $this->assertTrue(app(VfleetsService::class)->sincronizadoRecentemente());

        config(['services.vfleets.intervalo_sincronizacao' => 60]);

        $this->assertFalse(app(VfleetsService::class)->sincronizadoRecentemente());
    }
}
