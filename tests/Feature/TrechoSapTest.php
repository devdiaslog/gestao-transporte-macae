<?php

namespace Tests\Feature;

use App\Enums\PrazoPadrao;
use App\Models\TrechoSap;
use App\Models\User;
use App\Services\ImportadorTrechosSap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class TrechoSapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<int, string>>  $linhas
     */
    private function planilha(array $linhas): UploadedFile
    {
        $caminho = tempnam(sys_get_temp_dir(), 'trechos_').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($caminho);
        $writer->addRow(Row::fromValues(['Origem SAP', 'Destino SAP', 'Distância (km)', 'Prazo Hora Normal', 'Prazo Hora Expresso']));

        foreach ($linhas as $linha) {
            $writer->addRow(Row::fromValues($linha));
        }

        $writer->close();

        return new UploadedFile($caminho, 'trechos.xlsx', null, null, true);
    }

    private function admin(): User
    {
        return User::factory()->comPerfil('Administrador')->create();
    }

    public function test_exige_permissao_para_ver(): void
    {
        $this->actingAs(User::factory()->comPerfil('Operador')->create())
            ->get(route('trechos-sap.index'))
            ->assertForbidden();
    }

    public function test_cadastra_trecho_pela_tela(): void
    {
        $this->actingAs($this->admin())
            ->post(route('trechos-sap.store'), [
                'origem_sap' => 'ARM-MACAE',
                'destino_sap' => 'PACU',
                'km_trecho' => 164,
                'prazo_horas_normal' => 72,
                'prazo_horas_expresso' => 60,
                'prazo_padrao' => 'automatico',
            ])
            ->assertRedirect(route('trechos-sap.index'))
            ->assertSessionHas('success');

        $trecho = TrechoSap::firstOrFail();

        $this->assertSame('ARM MACAE > PACU', $trecho->chave_origem_destino);
        $this->assertSame(164.0, $trecho->km_trecho);
        $this->assertSame(PrazoPadrao::Automatico, $trecho->prazo_padrao);
    }

    /**
     * Grafias diferentes do mesmo lugar são o mesmo trecho: aceitar as duas
     * daria dois prazos para a mesma rota.
     */
    public function test_recusa_trecho_repetido_com_outra_grafia(): void
    {
        TrechoSap::create([
            'origem_sap' => 'ARM-MACAE',
            'destino_sap' => 'PACU',
            'prazo_padrao' => 'normal',
        ]);

        $this->actingAs($this->admin())
            ->post(route('trechos-sap.store'), [
                'origem_sap' => 'ARM MACAÉ',
                'destino_sap' => 'PACU',
                'prazo_padrao' => 'normal',
            ])
            ->assertSessionHasErrors('origem_sap');

        $this->assertSame(1, TrechoSap::count());
    }

    public function test_importa_a_planilha(): void
    {
        $this->actingAs($this->admin())
            ->post(route('trechos-sap.importar'), [
                'arquivo' => $this->planilha([
                    ['ARM-MACAE', 'PACU', '164', '72', '60'],
                    ['ARM-MACAE', 'BASE VITORIA', '381', '120', '108'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, TrechoSap::count());

        $vitoria = TrechoSap::where('destino_sap', 'BASE VITORIA')->firstOrFail();
        $this->assertSame(381.0, $vitoria->km_trecho);
        $this->assertSame(120, $vitoria->prazo_horas_normal);
        $this->assertSame(108, $vitoria->prazo_horas_expresso);
    }

    /**
     * O caso real da planilha: "BASES EXTERNAS" cobre de 13 km a 365 km, com
     * prazos de 48h a 120h. Escolher uma das linhas em silêncio daria prazo
     * errado ao item, então nada entra até a operação decidir.
     */
    public function test_recusa_a_planilha_quando_a_mesma_rota_diverge(): void
    {
        $resposta = $this->actingAs($this->admin())
            ->post(route('trechos-sap.importar'), [
                'arquivo' => $this->planilha([
                    ['BASES EXTERNAS', 'ARM-MACAE', '13,7', '48', '36'],
                    ['BASES EXTERNAS', 'ARM-MACAE', '365', '120', '108'],
                    ['ARM-MACAE', 'PACU', '164', '72', '60'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Nem o trecho sem conflito entra: o arquivo é recusado por inteiro.
        $this->assertSame(0, TrechoSap::count());

        $conflitos = session('conflitos');
        $this->assertCount(1, $conflitos);
        $this->assertStringContainsString('BASES EXTERNAS > ARM MACAE', $conflitos[0]);
        $this->assertStringContainsString('13,7', $conflitos[0]);
        $this->assertStringContainsString('365,0', $conflitos[0]);
    }

    /**
     * Linha repetida com os mesmos números é só redundância da planilha —
     * não há o que decidir, e a importação segue.
     */
    public function test_linha_repetida_identica_nao_e_conflito(): void
    {
        $this->actingAs($this->admin())
            ->post(route('trechos-sap.importar'), [
                'arquivo' => $this->planilha([
                    ['BASES MACAE I', 'ARM-MACAE', '0,4', '48', '36'],
                    ['BASES MACAE I', 'ARM-MACAE', '0,4', '48', '36'],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, TrechoSap::count());
    }

    public function test_reimportar_atualiza_sem_duplicar(): void
    {
        $usuario = $this->admin();

        $this->actingAs($usuario)->post(route('trechos-sap.importar'), [
            'arquivo' => $this->planilha([['ARM-MACAE', 'PACU', '164', '72', '60']]),
        ])->assertRedirect();

        $this->actingAs($usuario)->post(route('trechos-sap.importar'), [
            'arquivo' => $this->planilha([['ARM-MACAE', 'PACU', '168', '80', '64']]),
        ])->assertRedirect();

        $this->assertSame(1, TrechoSap::count());

        $trecho = TrechoSap::firstOrFail();
        $this->assertSame(168.0, $trecho->km_trecho);
        $this->assertSame(80, $trecho->prazo_horas_normal);
    }

    public function test_busca_encontra_por_qualquer_ponta(): void
    {
        TrechoSap::create(['origem_sap' => 'ARM-MACAE', 'destino_sap' => 'PACU', 'prazo_padrao' => 'normal']);
        TrechoSap::create(['origem_sap' => 'BASE VITORIA', 'destino_sap' => 'ARM-MACAE', 'prazo_padrao' => 'normal']);

        $this->actingAs($this->admin())
            ->get(route('trechos-sap.index', ['busca' => 'PACU']))
            ->assertOk()
            ->assertSee('PACU')
            ->assertDontSee('BASE VITORIA');
    }

    public function test_exporta_o_recorte_atual(): void
    {
        TrechoSap::create([
            'origem_sap' => 'ARM-MACAE',
            'destino_sap' => 'PACU',
            'km_trecho' => 164,
            'prazo_horas_normal' => 72,
            'prazo_padrao' => 'normal',
        ]);

        $resposta = $this->actingAs($this->admin())
            ->get(route('trechos-sap.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $resposta->getContent();
        $this->assertStringContainsString('ARM-MACAE', $csv);
        $this->assertStringContainsString('164,0', $csv);
    }

    public function test_edita_e_exclui(): void
    {
        $trecho = TrechoSap::create(['origem_sap' => 'ARM-MACAE', 'destino_sap' => 'PACU', 'prazo_padrao' => 'normal']);
        $usuario = $this->admin();

        $this->actingAs($usuario)
            ->put(route('trechos-sap.update', $trecho), [
                'origem_sap' => 'ARM-MACAE',
                'destino_sap' => 'PACU',
                'km_trecho' => 170,
                'prazo_horas_normal' => 80,
                'prazo_horas_expresso' => 64,
                'prazo_padrao' => 'expresso',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $trecho->refresh();
        $this->assertSame(80, $trecho->prazo_horas_normal);
        // Prazo padrão expresso: são as 64h que valem para o item.
        $this->assertSame(64, $trecho->horasVigentes());

        $this->actingAs($usuario)->delete(route('trechos-sap.destroy', $trecho))->assertRedirect();

        $this->assertSame(0, TrechoSap::count());
        $this->assertSame(1, TrechoSap::withTrashed()->count());
    }

    public function test_modelo_de_importacao_e_importavel(): void
    {
        $caminho = app(ImportadorTrechosSap::class)->gerarModelo();

        $resultado = app(ImportadorTrechosSap::class)->importar($caminho);

        $this->assertSame([], $resultado['conflitos']);
        $this->assertSame(2, $resultado['criados']);
    }
}
