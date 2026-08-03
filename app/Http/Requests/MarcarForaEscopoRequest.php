<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MarcarForaEscopoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A justificativa é obrigatória ao marcar: é ela que sustenta a decisão de
     * não atender perante o cliente. Ao reverter, não faz sentido exigi-la.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['integer', 'exists:demanda_itens,id'],
            'fora_escopo' => ['required', 'boolean'],
            'justificativa' => [
                'exclude_if:fora_escopo,false',
                'exclude_if:fora_escopo,0',
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Selecione ao menos um item.',
            'justificativa.required' => 'Explique por que o item não é de nossa responsabilidade.',
            'justificativa.min' => 'A justificativa precisa ser mais específica.',
        ];
    }
}
