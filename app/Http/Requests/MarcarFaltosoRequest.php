<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarcarFaltosoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * O motivo é obrigatório: é ele que a operação usa para cobrar o acerto
     * junto ao solicitante. A data de início da espera é informada pelo
     * usuário — o padrão da tela é o instante do registro.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['integer', 'exists:demanda_itens,id'],
            'motivo' => ['required', 'string', 'max:1000'],
            'faltoso_desde' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Selecione ao menos um item.',
            'motivo.required' => 'Descreva a pendência que trava o item.',
        ];
    }
}
