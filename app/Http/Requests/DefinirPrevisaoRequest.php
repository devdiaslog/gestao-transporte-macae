<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DefinirPrevisaoRequest extends FormRequest
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
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['integer', 'exists:demanda_itens,id'],
            'data_hora_previsao' => ['required', 'date'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Selecione ao menos um item.',
            'data_hora_previsao.required' => 'Informe a data e a hora previstas para a entrega.',
            'data_hora_previsao.date' => 'Data e hora da previsão inválidas.',
        ];
    }
}
