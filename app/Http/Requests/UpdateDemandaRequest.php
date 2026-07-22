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
            // Tipo escolhido fixa manualmente; vazio volta ao automático pelos itens.
            'tipo_demanda' => ['nullable', Rule::enum(TipoDemanda::class)],
            'equipamento_id' => ['nullable', 'exists:equipamentos,id'],
            'documento_demanda' => ['nullable', 'string', 'max:100'],
            'data_hora_inicio_demanda' => ['nullable', 'date'],
            // O fim é automático (maior entrega dos itens), mas o operador pode
            // ajustá-lo quando o SAP traz hora genérica; ajustado, passa a ser dele.
            'data_hora_fim_demanda' => ['nullable', 'date', 'after_or_equal:data_hora_inicio_demanda'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'data_hora_inicio_demanda' => 'início da demanda',
            'data_hora_fim_demanda' => 'fim da demanda',
        ];
    }
}
