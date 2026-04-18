<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubDivisaoRequest extends FormRequest
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
            'divisao_id' => ['required', 'exists:divisoes,id'],
            'nome' => ['required', 'string', 'unique:sub_divisoes,nome'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'divisao_id.required' => 'A divisão é obrigatória.',
            'divisao_id.exists' => 'A divisão selecionada não existe.',
            'nome.required' => 'O nome é obrigatório.',
            'nome.unique' => 'Já existe uma subdivisão com este nome.',
            'status.required' => 'O status é obrigatório.',
        ];
    }
}
