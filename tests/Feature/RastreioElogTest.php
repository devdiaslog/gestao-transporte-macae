<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Demanda;
use App\Models\DemandaCapturaElog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class RastreioElogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<string, mixed>>  $veiculos
     * @return array{elog_iniciadas: int, elog_finalizadas: int}
     */
    private function rastrear(array $veiculos, Carbon $agora): array
    {
        $metodo = new ReflectionMethod(DashboardController::class, 'rastrearElog');
        $metodo->setAccessible(true);

        return $metodo->invoke(app(DashboardController::class), $veiculos, $agora);
    }

    private function veiculo(string $documento): array
    {
        return [
            'divisionId' => 114,
            'licensePlate' => 'ABC1D23',
            'document' => ['documentCode' => $documento],
        ];
    }

    public function test_marca_inicio_e_registra_captura_na_primeira_aparicao(): void
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => 509300001]);
        $t0 = Carbon::parse('2026-07-24 08:00:00');

        $resultado = $this->rastrear([$this->veiculo('509300001')], $t0);

        $this->assertSame(1, $resultado['elog_iniciadas']);
        $this->assertSame(0, $resultado['elog_finalizadas']);

        $demanda->refresh();
        $this->assertTrue($t0->equalTo($demanda->data_hora_inicio_elog));
        $this->assertNull($demanda->data_hora_fim_elog);

        $captura = DemandaCapturaElog::where('demanda_id', $demanda->id)->firstOrFail();
        $this->assertTrue($t0->equalTo($captura->primeira_captura));
        $this->assertTrue($t0->equalTo($captura->ultima_captura));
    }

    public function test_capturas_seguintes_nao_reiniciam_e_atualizam_a_ultima_presenca(): void
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => 509300002]);
        $t0 = Carbon::parse('2026-07-24 08:00:00');
        $t1 = Carbon::parse('2026-07-24 08:30:00');

        $this->rastrear([$this->veiculo('509300002')], $t0);
        $resultado = $this->rastrear([$this->veiculo('509300002')], $t1);

        $this->assertSame(0, $resultado['elog_iniciadas']);

        $captura = DemandaCapturaElog::where('demanda_id', $demanda->id)->firstOrFail();
        $this->assertTrue($t0->equalTo($captura->primeira_captura));
        $this->assertTrue($t1->equalTo($captura->ultima_captura));
        // Início preservado da primeira captura.
        $this->assertTrue($t0->equalTo($demanda->refresh()->data_hora_inicio_elog));
    }

    public function test_marca_fim_quando_a_demanda_some_da_captura(): void
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => 509300003]);
        $outra = Demanda::factory()->create(['numero_demanda' => 509300099]);
        $t0 = Carbon::parse('2026-07-24 08:00:00');
        $t1 = Carbon::parse('2026-07-24 09:00:00');

        // t0: a demanda está no E-log.
        $this->rastrear([$this->veiculo('509300003')], $t0);

        // t1: sumiu (só a outra aparece) → concluída no TMS.
        $resultado = $this->rastrear([$this->veiculo('509300099')], $t1);

        $this->assertSame(1, $resultado['elog_finalizadas']);

        $demanda->refresh();
        // O fim é a última presença confirmada (t0), não o instante da captura.
        $this->assertTrue($t0->equalTo($demanda->data_hora_fim_elog));
    }

    public function test_reaparecer_apos_concluida_limpa_o_fim(): void
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => 509300004]);
        $t0 = Carbon::parse('2026-07-24 08:00:00');
        $t1 = Carbon::parse('2026-07-24 09:00:00');
        $t2 = Carbon::parse('2026-07-24 10:00:00');

        $this->rastrear([$this->veiculo('509300004')], $t0);
        $this->rastrear([], $t1);
        $this->assertNotNull($demanda->refresh()->data_hora_fim_elog);

        // Voltou a aparecer: o atendimento não estava concluído.
        $this->rastrear([$this->veiculo('509300004')], $t2);
        $this->assertNull($demanda->refresh()->data_hora_fim_elog);
    }

    public function test_fim_so_e_marcado_uma_vez(): void
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => 509300005]);
        $t0 = Carbon::parse('2026-07-24 08:00:00');

        $this->rastrear([$this->veiculo('509300005')], $t0);
        $primeiro = $this->rastrear([], Carbon::parse('2026-07-24 09:00:00'));
        $segundo = $this->rastrear([], Carbon::parse('2026-07-24 10:00:00'));

        $this->assertSame(1, $primeiro['elog_finalizadas']);
        $this->assertSame(0, $segundo['elog_finalizadas']);
    }
}
