<?php

namespace Tests\Feature;

use App\Enums\FonteDemanda;
use App\Enums\TipoCadastro;
use App\Http\Controllers\DashboardController;
use App\Models\Demanda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class CapturaElogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<string, mixed>>  $veiculos
     */
    private function capturar(array $veiculos): int
    {
        $metodo = new ReflectionMethod(DashboardController::class, 'registrarDemandasNovas');
        $metodo->setAccessible(true);

        return $metodo->invoke(app(DashboardController::class), $veiculos);
    }

    public function test_cadastra_apenas_numero_e_veiculo_sem_criar_itens(): void
    {
        $criados = $this->capturar([
            [
                'divisionId' => 114,
                'licensePlate' => 'ABC1D23',
                'document' => ['documentCode' => '509888777'],
            ],
        ]);

        $this->assertSame(1, $criados);

        $demanda = Demanda::where('numero_demanda', 509888777)->firstOrFail();

        // Só o essencial: número, fonte (derivada do número) e cadastro por integração.
        $this->assertSame(FonteDemanda::SapLt, $demanda->fonte_demanda);
        $this->assertSame(TipoCadastro::Integracao, $demanda->tipo_cadastro);

        // Nada de rota, itens ou tipo — isso vem depois pela importação do SAP.
        $this->assertSame(0, $demanda->itens()->count());
        $this->assertNull($demanda->tipo_demanda);
        $this->assertNull($demanda->prazo_demanda);
    }

    public function test_ignora_veiculos_de_outra_divisao(): void
    {
        $criados = $this->capturar([
            [
                'divisionId' => 999,
                'licensePlate' => 'ABC1D23',
                'document' => ['documentCode' => '509000123'],
            ],
        ]);

        $this->assertSame(0, $criados);
        $this->assertSame(0, Demanda::count());
    }

    public function test_nao_recria_demanda_ja_existente(): void
    {
        Demanda::factory()->create(['numero_demanda' => 509888777]);

        $criados = $this->capturar([
            [
                'divisionId' => 114,
                'licensePlate' => 'ABC1D23',
                'document' => ['documentCode' => '509888777'],
            ],
        ]);

        $this->assertSame(0, $criados);
        $this->assertSame(1, Demanda::where('numero_demanda', 509888777)->count());
    }
}
