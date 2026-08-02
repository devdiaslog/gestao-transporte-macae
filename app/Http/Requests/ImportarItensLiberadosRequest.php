<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportarItensLiberadosRequest extends FormRequest
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
            'arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],

            /**
             * Marcado quando a planilha é o export completo dos itens em
             * cobrança: os que não constam nela vão para conferência.
             */
            'marcar_ausentes' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'arquivo.required' => 'Selecione a planilha para importar.',
            'arquivo.file' => 'O envio não é um arquivo válido.',
            'arquivo.mimes' => 'A planilha deve estar em formato .xlsx ou .xls.',
            'arquivo.max' => 'A planilha não pode ultrapassar 10 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'arquivo' => 'planilha',
        ];
    }
}
