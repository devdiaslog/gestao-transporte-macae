<?php

namespace Database\Factories;

use App\Enums\StatusDemanda;
use App\Enums\TipoCadastro;
use App\Models\Demanda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Demanda>
 */
class DemandaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_demanda' => fake()->unique()->numberBetween(500000000, 509999999),
            'tipo_cadastro' => TipoCadastro::Integracao,
            'status_demanda' => StatusDemanda::Pendente,
            'status_auditoria' => false,
        ];
    }
}
