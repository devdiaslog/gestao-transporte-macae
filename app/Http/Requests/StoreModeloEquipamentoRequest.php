<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreModeloEquipamentoRequest extends FormRequest
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
            'tipo_equipamento_id' => ['required', 'exists:tipos_equipamentos,id'],
            'nome' => ['required', 'string', 'unique:modelos_equipamentos,nome'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_equipamento_id.required' => 'O tipo de equipamento é obrigatório.',
            'tipo_equipamento_id.exists' => 'O tipo de equipamento selecionado não existe.',
            'nome.required' => 'O nome é obrigatório.',
            'nome.unique' => 'Já existe um modelo de equipamento com este nome.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
