<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RenegociarPrazoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O motivo é obrigatório: alterar o prazo muda o que o cliente cobra, e
     * quem olhar depois precisa saber com base em quê a data mudou.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['integer', 'exists:demanda_itens,id'],
            'prazo_item' => ['required', 'date'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Selecione ao menos um item.',
            'prazo_item.required' => 'Informe o novo prazo acordado.',
            'prazo_item.date' => 'Data e hora do prazo inválidas.',
            'motivo.required' => 'Registre com quem e por que o prazo foi renegociado.',
            'motivo.min' => 'O motivo precisa ser mais específico.',
        ];
    }
}
