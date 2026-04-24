<?php

namespace Database\Factories;

use App\Models\Equipamento;
use App\Models\Ocorrencia;
use App\Models\TipoOcorrencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ocorrencia>
 */
class OcorrenciaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'id_veiculo' => Equipamento::factory(),
            'id_tipo' => TipoOcorrencia::factory(),
            'id_responsavel' => null,
            'id_justificativa' => null,
            'data_hora_inicio' => $inicio,
            'data_hora_fim' => fake()->optional()->dateTimeBetween($inicio, 'now'),
            'observacao' => fake()->optional()->sentence(),
        ];
    }
}
