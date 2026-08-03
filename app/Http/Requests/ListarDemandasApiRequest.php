<?php

namespace App\Http\Requests;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarDemandasApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtros da consulta de demandas. Todos opcionais: sem filtro nenhum a
     * consulta devolve tudo, paginado.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fonte' => ['sometimes', 'array'],
            'fonte.*' => [Rule::enum(FonteDemanda::class)],

            'status' => ['sometimes', 'array'],
            'status.*' => [Rule::enum(StatusDemanda::class)],

            'sem_itens' => ['sometimes', 'boolean'],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Aceita fonte e status também como texto separado por vírgula, que é como
     * saem de uma query string escrita à mão.
     */
    protected function prepareForValidation(): void
    {
        foreach (['fonte', 'status'] as $filtro) {
            $valor = $this->input($filtro);

            if (is_string($valor)) {
                $this->merge([
                    $filtro => array_values(array_filter(array_map('trim', explode(',', $valor)))),
                ]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fonte.*.Illuminate\Validation\Rules\Enum' => 'Fonte inválida. Use sap_lt ou sap_tm.',
        ];
    }
}
