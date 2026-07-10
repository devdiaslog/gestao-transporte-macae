<?php

namespace App\Http\Requests;

use App\Enums\StatusDemanda;
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
            // numero_demanda não pode ser alterado
            'tipo_demanda' => ['nullable', Rule::enum(TipoDemanda::class)],
            'equipamento_id' => ['nullable', 'exists:equipamentos,id'],
            'local_origem_id' => ['nullable', 'exists:locais,id'],
            'local_destino_id' => ['nullable', 'exists:locais,id'],
            'prazo_atendimento_demanda' => ['nullable', 'date'],
            'status_demanda' => ['nullable', Rule::enum(StatusDemanda::class)],

            'data_hora_inicio_carregamento' => ['nullable', 'date'],
            'data_hora_fim_carregamento' => [
                'nullable',
                'date',
                'after:data_hora_inicio_carregamento',
            ],
            'data_hora_saida_origem' => ['nullable', 'date'],
            'data_hora_chegada_destino' => ['nullable', 'date'],
            'data_hora_inicio_descarregamento' => ['nullable', 'date'],
            'data_hora_fim_descarregamento' => [
                'nullable',
                'date',
                'after:data_hora_inicio_descarregamento',
            ],
            'observacao_adicional' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'data_hora_fim_carregamento.after' => 'O fim do carregamento deve ser posterior ao início.',
            'data_hora_fim_descarregamento.after' => 'O fim do descarregamento deve ser posterior ao início.',
        ];
    }
}
