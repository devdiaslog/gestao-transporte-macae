<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AjustarRotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Origem e destino são opcionais isoladamente — dá para corrigir só uma
     * ponta do trecho — mas ao menos uma precisa vir preenchida.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1'],
            'itens.*' => ['integer', 'exists:demanda_itens,id'],
            'local_origem' => ['nullable', 'required_without:local_destino', 'string', 'max:255'],
            'local_destino' => ['nullable', 'required_without:local_origem', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Selecione ao menos um item.',
            'local_origem.required_without' => 'Informe a origem, o destino ou ambos.',
            'local_destino.required_without' => 'Informe a origem, o destino ou ambos.',
        ];
    }
}
