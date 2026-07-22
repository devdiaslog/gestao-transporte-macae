<?php

namespace Tests\Feature;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\TipoDemanda;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Services\ImportadorDemandas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportadorDemandasTest extends TestCase
{
    use RefreshDatabase;

    private function planilha(): string
    {
        return base_path('tests/Fixtures/export_sap_amostra.xlsx');
    }

    private function importar(): array
    {
        return app(ImportadorDemandas::class)->importar($this->planilha());
    }

    public function test_agrupa_itens_na_mesma_demanda_pelo_numero_da_nota(): void
    {
        $resultado = $this->importar();

        $this->assertSame(2, $resultado['demandas_criadas']);
        $this->assertSame(3, $resultado['itens_criados']);
        $this->assertSame([], $resultado['erros']);

        $this->assertSame(2, Demanda::count());
        $this->assertSame(2, Demanda::where('numero_demanda', 509111111)->first()->itens()->count());
    }

    public function test_reimportar_atualiza_os_itens_sem_duplicar(): void
    {
        $this->importar();
        $segunda = $this->importar();

        $this->assertSame(0, $segunda['demandas_criadas']);
        $this->assertSame(0, $segunda['itens_criados']);
        $this->assertSame(3, $segunda['itens_atualizados']);

        $this->assertSame(2, Demanda::count());
        $this->assertSame(3, DemandaItem::count());
    }

    public function test_grava_os_campos_do_item_vindos_da_planilha(): void
    {
        $this->importar();

        $item = DemandaItem::where('numero_rt', '326000001')->firstOrFail();

        $this->assertSame('PACU', $item->local_origem);
        $this->assertSame('ARM-MACAE', $item->local_destino);
        $this->assertSame('PACU-CAIS 2', $item->descricao_local_retirada);
        $this->assertSame('Carga A', $item->descricao_item);
        $this->assertSame(StatusItemDemanda::Pendente, $item->status_item);
        $this->assertSame('24/07/2026 10:00', $item->prazo_item->format('d/m/Y H:i'));
    }

    public function test_deriva_fonte_tipo_e_status_da_demanda_importada(): void
    {
        $this->importar();

        // Origem PACU (ponto-chave) e destinos comuns → Backload.
        $backload = Demanda::where('numero_demanda', 509111111)->firstOrFail();
        $this->assertSame(FonteDemanda::SapLt, $backload->fonte_demanda);
        $this->assertSame(TipoDemanda::Backload, $backload->tipo_demanda);
        $this->assertSame(StatusDemanda::Pendente, $backload->status_demanda);

        // Destino BMAC (ponto-chave) e item entregue → Load e finalizado.
        $load = Demanda::where('numero_demanda', 509222222)->firstOrFail();
        $this->assertSame(TipoDemanda::Load, $load->tipo_demanda);
        $this->assertSame(StatusDemanda::Finalizado, $load->status_demanda);
    }

    public function test_modelo_gerado_e_compativel_com_o_proprio_importador(): void
    {
        $importador = app(ImportadorDemandas::class);

        $caminho = $importador->gerarModelo();
        $this->assertFileExists($caminho);

        $resultado = $importador->importar($caminho);

        $this->assertSame([], $resultado['erros']);
        $this->assertGreaterThan(0, $resultado['demandas_criadas']);
        $this->assertGreaterThan(0, $resultado['itens_criados']);

        @unlink($caminho);
    }

    public function test_prazo_da_demanda_usa_o_menor_item_ainda_exequivel(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 21)->setTime(8, 0));

        $this->importar();

        // Itens em 24/07 e 25/07: o menor alcançável é 24/07.
        $demanda = Demanda::where('numero_demanda', 509111111)->firstOrFail();

        $this->assertSame('24/07/2026 10:00', $demanda->prazo_demanda->format('d/m/Y H:i'));
    }
}
