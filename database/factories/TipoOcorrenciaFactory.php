<?php

namespace Database\Factories;

use App\Models\TipoOcorrencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoOcorrencia>
 */
class TipoOcorrenciaFactory extends Factory
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
        ];
    }
}
