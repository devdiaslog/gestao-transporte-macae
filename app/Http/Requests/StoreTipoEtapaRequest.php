<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTipoEtapaRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:150', 'unique:tipo_etapas,nome'],
            'necessita_cerca' => ['boolean'],
            'ativo' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do tipo de etapa é obrigatório.',
            'nome.unique' => 'Já existe um tipo de etapa com este nome.',
        ];
    }
}
