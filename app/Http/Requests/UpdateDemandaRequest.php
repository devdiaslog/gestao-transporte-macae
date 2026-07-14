<?php

namespace App\Http\Requests;

use App\Enums\TipoDemanda;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_demanda' => ['nullable', Rule::enum(TipoDemanda::class)],
            'equipamento_id' => ['nullable', 'exists:equipamentos,id'],
            'documento_demanda' => ['nullable', 'string', 'max:100'],
            'origem' => ['nullable', 'string', 'max:500'],
            'destino' => ['nullable', 'string', 'max:500'],
            'prazo_referencia' => ['nullable', 'date'],
            'data_hora_inicio_demanda' => ['nullable', 'date'],
            'data_hora_fim_demanda' => ['nullable', 'date'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
