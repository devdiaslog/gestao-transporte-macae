<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJustificativaRequest extends FormRequest
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
            'ativo' => ['boolean'],
            'obrigar_observacao' => ['boolean'],
            'tipos' => ['nullable', 'array'],
            'tipos.*' => ['integer', 'exists:tipos_ocorrencia,id_tipo'],
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
