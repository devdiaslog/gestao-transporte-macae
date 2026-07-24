<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AtualizarPrazoEtapaRequest extends FormRequest
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
            'prazo_item' => ['required', 'date'],
            'itens' => ['required', 'array', 'min:1'],
            // Só aceita itens que realmente pertencem à demanda da rota.
            'itens.*' => [
                'integer',
                Rule::exists('demanda_itens', 'id')
                    ->where('demanda_id', $this->route('demanda')->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prazo_item.required' => 'Informe o prazo a aplicar na etapa.',
            'itens.required' => 'Nenhum item da etapa foi informado.',
            'itens.*.exists' => 'Um dos itens não pertence a esta demanda.',
        ];
    }
}
