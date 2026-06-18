<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEtapaRequest extends FormRequest
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
            'equipamento_id' => ['required', 'integer', 'exists:equipamentos,id'],
            'tipo_etapa_id' => ['required', 'integer', 'exists:tipo_etapas,id'],
            'local_etapa_id' => ['required', 'integer', 'exists:local_etapas,id'],
            'motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],
            'documento' => ['nullable', 'string', 'max:100'],
            'data_hora_inicio' => ['required', 'date'],
            'data_hora_fim' => ['nullable', 'date', 'after_or_equal:data_hora_inicio'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tipo_etapa_id.required' => 'O tipo de etapa é obrigatório.',
            'local_etapa_id.required' => 'O local é obrigatório.',
            'data_hora_inicio.required' => 'A data/hora de início é obrigatória.',
            'data_hora_fim.after_or_equal' => 'A data/hora de fim deve ser igual ou posterior ao início.',
        ];
    }
}
