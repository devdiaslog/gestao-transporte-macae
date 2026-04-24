<?php

namespace Database\Factories;

use App\Enums\TipoResponsavel;
use App\Models\Responsavel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Responsavel>
 */
class ResponsavelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'tipo' => fake()->randomElement(TipoResponsavel::cases()),
            'ativo' => true,
        ];
    }

    public function interno(): static
    {
        return $this->state(['tipo' => TipoResponsavel::Interno]);
    }

    public function externo(): static
    {
        return $this->state(['tipo' => TipoResponsavel::Externo]);
    }

    public function inativo(): static
    {
        return $this->state(['ativo' => false]);
    }
}
