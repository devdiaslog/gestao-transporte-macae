<?php

namespace Database\Factories;

use App\Models\Justificativa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Justificativa>
 */
class JustificativaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'descricao' => fake()->sentence(3),
            'ativo' => true,
            'obrigar_observacao' => false,
        ];
    }

    public function inativo(): static
    {
        return $this->state(['ativo' => false]);
    }

    public function obrigaObservacao(): static
    {
        return $this->state(['obrigar_observacao' => true]);
    }
}
