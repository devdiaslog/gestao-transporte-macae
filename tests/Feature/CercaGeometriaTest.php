<?php

namespace Tests\Feature;

use App\Models\Cerca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CercaGeometriaTest extends TestCase
{
    use RefreshDatabase;

    /** Triângulo simples, válido. */
    private const QUADRADO = [
        [-22.370, -41.780],
        [-22.370, -41.770],
        [-22.380, -41.770],
        [-22.380, -41.780],
    ];

    /**
     * @param  array<int, array<int, float>>|string  $poligono
     * @return array<string, mixed>
     */
    private function dados(array|string $poligono, array $extra = []): array
    {
        return array_merge([
            'nome' => 'Cerca de Teste',
            'atividade' => null,
            'status' => '1',
            'tempo_minimo' => 10,
            'tempo_maximo' => 60,
            'poligono' => is_string($poligono) ? $poligono : json_encode($poligono),
        ], $extra);
    }

    public function test_cria_cerca_com_poligono_valido(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados(self::QUADRADO))
            ->assertRedirect(route('cercas.index'));

        $cerca = Cerca::firstOrFail();

        $this->assertCount(4, $cerca->poligono);
        // A ordem desenhada é preservada (não há reordenação por ângulo).
        $this->assertSame([-22.370, -41.780], $cerca->poligono[0]);
        $this->assertSame([-22.370, -41.770], $cerca->poligono[1]);
    }

    public function test_poligono_e_obrigatorio(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados(''))
            ->assertSessionHasErrors('poligono');

        $this->assertSame(0, Cerca::count());
    }

    public function test_recusa_poligono_com_menos_de_tres_vertices(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados([[-22.37, -41.78], [-22.38, -41.77]]))
            ->assertSessionHasErrors('poligono');
    }

    public function test_recusa_poligono_que_se_cruza(): void
    {
        // "Borboleta": os lados opostos se cruzam no meio.
        $borboleta = [
            [-22.370, -41.780],
            [-22.380, -41.770],
            [-22.370, -41.770],
            [-22.380, -41.780],
        ];

        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados($borboleta))
            ->assertSessionHasErrors('poligono');
    }

    public function test_recusa_coordenadas_fora_do_intervalo(): void
    {
        $invalido = [[-22.37, -41.78], [200.0, -41.77], [-22.38, -41.77]];

        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados($invalido))
            ->assertSessionHasErrors('poligono');
    }

    public function test_recusa_coordenadas_nao_numericas(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados('[[-22.37,-41.78],["abc","x"],[-22.38,-41.77]]'))
            ->assertSessionHasErrors('poligono');
    }

    public function test_aceita_poligono_concavo_em_formato_de_l(): void
    {
        // Forma em "L" — a implementação antiga a deformava ao reordenar
        // os vértices por ângulo em torno do centroide.
        $formaL = [
            [-22.370, -41.780],
            [-22.370, -41.770],
            [-22.375, -41.770],
            [-22.375, -41.775],
            [-22.380, -41.775],
            [-22.380, -41.780],
        ];

        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados($formaL))
            ->assertRedirect(route('cercas.index'));

        $this->assertSame($formaL, Cerca::firstOrFail()->poligono);
    }

    public function test_tempo_maximo_nao_pode_ser_menor_que_o_minimo(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('cercas.store'), $this->dados(self::QUADRADO, [
                'tempo_minimo' => 100,
                'tempo_maximo' => 10,
            ]))
            ->assertSessionHasErrors('tempo_maximo');
    }

    public function test_edicao_recebe_as_demais_cercas_como_referencia(): void
    {
        $alvo = Cerca::create($this->dadosModelo('Alvo', self::QUADRADO));
        $outra = Cerca::create($this->dadosModelo('Vizinha', self::QUADRADO));

        $this->actingAs(User::factory()->create())
            ->get(route('cercas.edit', $alvo))
            ->assertOk()
            ->assertViewHas('cercasExistentes', function ($vizinhas) use ($outra) {
                // A própria cerca editada não entra na lista.
                return $vizinhas->count() === 1 && $vizinhas->first()['nome'] === $outra->nome;
            });
    }

    /**
     * @param  array<int, array<int, float>>  $poligono
     * @return array<string, mixed>
     */
    private function dadosModelo(string $nome, array $poligono): array
    {
        return [
            'nome' => $nome,
            'poligono' => $poligono,
            'status' => true,
            'tempo_minimo' => 10,
            'tempo_maximo' => 60,
        ];
    }
}
