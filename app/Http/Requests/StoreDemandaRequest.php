<?php

namespace App\Http\Requests;

use App\Enums\TipoDemanda;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandaRequest extends FormRequest
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
        $isIntegracao = $this->input('tipo_cadastro') === 'integracao';

        return [
            'numero_demanda' => ['required', 'integer', 'digits_between:9,10', 'unique:demandas,numero_demanda'],
            'tipo_cadastro' => ['required', 'in:manual,integracao'],

            // Campos opcionais — validados só quando presentes
            'tipo_demanda' => ['nullable', Rule::enum(TipoDemanda::class)],
            'equipamento_id' => ['nullable', 'exists:equipamentos,id'],
            'local_origem_id' => ['nullable', 'exists:locais,id'],
            'local_destino_id' => ['nullable', 'exists:locais,id'],
            'prazo_atendimento_demanda' => ['nullable', 'date'],
            'data_hora_agendamento' => ['nullable', 'date'],

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
            'numero_demanda.required' => 'O número da demanda é obrigatório.',
            'numero_demanda.integer' => 'O número da demanda deve ser um valor inteiro.',
            'numero_demanda.digits_between' => 'O número da demanda deve ter entre 9 e 10 dígitos.',
            'numero_demanda.unique' => 'Este número de demanda já está cadastrado.',
            'tipo_cadastro.required' => 'O tipo de cadastro é obrigatório.',
            'data_hora_fim_carregamento.after' => 'O fim do carregamento deve ser posterior ao início.',
            'data_hora_fim_descarregamento.after' => 'O fim do descarregamento deve ser posterior ao início.',
        ];
    }
}
