<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTipoOcorrenciaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'descricao' => ['required', 'string', 'max:255'],
            'responsaveis' => ['nullable', 'array'],
            'responsaveis.*' => ['integer', 'exists:responsaveis,id_responsavel'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'descricao.required' => 'A descrição é obrigatória.',
        ];
    }
}
