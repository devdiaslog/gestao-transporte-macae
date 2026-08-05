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
    private function planilha(array $linhas, bool $comCidades = false): UploadedFile
    {
        $caminho = tempnam(sys_get_temp_dir(), 'trechos_').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($caminho);
        $writer->addRow(Row::fromValues(array_merge(
            ['Origem SAP', 'Destino SAP'],
            $comCidades ? ['Cidade Origem', 'Cidade Destino'] : [],
            ['Distância (km)', 'Prazo Hora Normal', 'Prazo Hora Expresso'],
        )));

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
                'prazo_padrao' => 'manual',
            ])
            ->assertRedirect(route('trechos-sap.index'))
            ->assertSessionHas('success');

        $trecho = TrechoSap::firstOrFail();

        $this->assertSame('ARM MACAE > PACU', $trecho->chave_origem_destino);
        $this->assertSame(164.0, $trecho->km_trecho);
        $this->assertSame(PrazoPadrao::Manual, $trecho->prazo_padrao);
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

    /**
     * Sem escolha explícita, o trecho nasce no prazo normal — o expresso é
     * exceção, e o automático depende de uma regra que ainda não existe.
     */
    public function test_prazo_padrao_nasce_normal(): void
    {
        app(ImportadorTrechosSap::class)->importar($this->planilha([
            ['ARM-MACAE', 'PACU', '164', '72', '60'],
        ])->getRealPath());

        $this->assertSame(PrazoPadrao::Normal, TrechoSap::firstOrFail()->prazo_padrao);
    }

    /**
     * A rota nasce vazia quando a importação de itens a descobre. O filtro
     * separa o que ainda depende da operação do que já está pronto para
     * calcular prazo.
     */
    public function test_filtra_o_que_falta_preencher(): void
    {
        // Completo.
        TrechoSap::create([
            'origem_sap' => 'ARM-MACAE', 'destino_sap' => 'PACU',
            'km_trecho' => 164, 'prazo_horas_normal' => 72, 'prazo_padrao' => 'normal',
        ]);
        // Sem distância.
        TrechoSap::create([
            'origem_sap' => 'ARM-MACAE', 'destino_sap' => 'SEM KM',
            'prazo_horas_normal' => 48, 'prazo_padrao' => 'normal',
        ]);
        // Sem prazo.
        TrechoSap::create([
            'origem_sap' => 'ARM-MACAE', 'destino_sap' => 'SEM PRAZO',
            'km_trecho' => 10, 'prazo_padrao' => 'normal',
        ]);

        $usuario = $this->admin();

        $this->actingAs($usuario)
            ->get(route('trechos-sap.index', ['preenchimento' => 'incompletos']))
            ->assertOk()
            ->assertSee('SEM KM')
            ->assertSee('SEM PRAZO')
            ->assertDontSee('PACU');

        $this->actingAs($usuario)
            ->get(route('trechos-sap.index', ['preenchimento' => 'completos']))
            ->assertOk()
            ->assertSee('PACU')
            ->assertDontSee('SEM KM');
    }

    /**
     * Trecho no padrão expresso depende das horas de expresso; sem elas, e sem
     * o normal para cair, ainda falta preencher.
     */
    public function test_expresso_sem_horas_conta_como_a_preencher(): void
    {
        TrechoSap::create([
            'origem_sap' => 'ARM-MACAE', 'destino_sap' => 'SO EXPRESSO',
            'km_trecho' => 10, 'prazo_padrao' => 'expresso',
        ]);

        $this->actingAs($this->admin())
            ->get(route('trechos-sap.index', ['preenchimento' => 'incompletos']))
            ->assertOk()
            ->assertSee('SO EXPRESSO');
    }

    public function test_atalho_mostra_quantas_rotas_esperam_preenchimento(): void
    {
        TrechoSap::create(['origem_sap' => 'A', 'destino_sap' => 'B', 'prazo_padrao' => 'normal']);
        TrechoSap::create(['origem_sap' => 'C', 'destino_sap' => 'D', 'prazo_padrao' => 'normal']);

        $this->actingAs($this->admin())
            ->get(route('trechos-sap.index'))
            ->assertOk()
            ->assertSee('rotas esperam distância e prazo', escape: false);
    }

    public function test_export_respeita_o_filtro_de_preenchimento(): void
    {
        TrechoSap::create([
            'origem_sap' => 'ARM-MACAE', 'destino_sap' => 'PACU',
            'km_trecho' => 164, 'prazo_horas_normal' => 72, 'prazo_padrao' => 'normal',
        ]);
        TrechoSap::create(['origem_sap' => 'ARM-MACAE', 'destino_sap' => 'VAZIO', 'prazo_padrao' => 'normal']);

        $csv = $this->actingAs($this->admin())
            ->get(route('trechos-sap.export', ['preenchimento' => 'incompletos']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('VAZIO', $csv);
        $this->assertStringNotContainsString('PACU', $csv);
    }

    public function test_importa_as_cidades_do_trecho(): void
    {
        $this->actingAs($this->admin())
            ->post(route('trechos-sap.importar'), [
                'arquivo' => $this->planilha([
                    ['ARM-MACAE', 'PACU', 'Macaé', 'São João da Barra', '164', '72', '60'],
                ], comCidades: true),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $trecho = TrechoSap::firstOrFail();

        $this->assertSame('Macaé', $trecho->cidade_origem);
        $this->assertSame('São João da Barra', $trecho->cidade_destino);
    }

    public function test_reimportar_atualiza_as_cidades(): void
    {
        $usuario = $this->admin();

        $this->actingAs($usuario)->post(route('trechos-sap.importar'), [
            'arquivo' => $this->planilha([
                ['ARM-MACAE', 'PACU', 'Macae', 'Sao Joao', '164', '72', '60'],
            ], comCidades: true),
        ]);

        $this->actingAs($usuario)->post(route('trechos-sap.importar'), [
            'arquivo' => $this->planilha([
                ['ARM-MACAE', 'PACU', 'Macaé', 'São João da Barra', '164', '72', '60'],
            ], comCidades: true),
        ]);

        $trecho = TrechoSap::firstOrFail();

        $this->assertSame(1, TrechoSap::count());
        $this->assertSame('Macaé', $trecho->cidade_origem);
        $this->assertSame('São João da Barra', $trecho->cidade_destino);
    }

    /**
     * Cidade é descritiva: planilha sem a coluna não apaga o que a operação já
     * preencheu na tela.
     */
    public function test_planilha_sem_cidades_nao_apaga_as_existentes(): void
    {
        $usuario = $this->admin();

        $this->actingAs($usuario)->post(route('trechos-sap.importar'), [
            'arquivo' => $this->planilha([
                ['ARM-MACAE', 'PACU', 'Macaé', 'São João da Barra', '164', '72', '60'],
            ], comCidades: true),
        ]);

        // Reimporta pelo layout antigo, sem as colunas de cidade.
        $this->actingAs($usuario)->post(route('trechos-sap.importar'), [
            'arquivo' => $this->planilha([['ARM-MACAE', 'PACU', '168', '80', '64']]),
        ]);

        $trecho = TrechoSap::firstOrFail();

        $this->assertSame(168.0, $trecho->km_trecho);
        $this->assertSame('Macaé', $trecho->cidade_origem);
        $this->assertSame('São João da Barra', $trecho->cidade_destino);
    }

    public function test_modelo_traz_as_colunas_de_cidade(): void
    {
        $resultado = app(ImportadorTrechosSap::class)
            ->importar(app(ImportadorTrechosSap::class)->gerarModelo());

        $this->assertSame(2, $resultado['criados']);
        $this->assertSame('Macaé', TrechoSap::where('destino_sap', 'PACU')->firstOrFail()->cidade_origem);
    }
}
