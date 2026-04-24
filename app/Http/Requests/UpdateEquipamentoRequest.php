<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipamentoRequest extends FormRequest
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
            'tipo_id' => ['required', 'exists:tipos_equipamentos,id'],
            'modelo_id' => ['nullable', 'exists:modelos_equipamentos,id'],
            'divisao_id' => ['nullable', 'exists:divisoes,id'],
            'sub_divisao_id' => ['nullable', 'exists:sub_divisoes,id'],
            'id_elog' => ['nullable', 'string'],
            'id_rastreador' => ['nullable', 'string', 'max:255'],
            'prefixo' => ['nullable', 'string'],
            'placa' => ['required', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_id.required' => 'O tipo de equipamento é obrigatório.',
            'tipo_id.exists' => 'O tipo de equipamento selecionado não existe.',
            'modelo_id.exists' => 'O modelo de equipamento selecionado não existe.',
            'divisao_id.exists' => 'A divisão selecionada não existe.',
            'sub_divisao_id.exists' => 'A subdivisão selecionada não existe.',
            'placa.required' => 'A placa é obrigatória.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
