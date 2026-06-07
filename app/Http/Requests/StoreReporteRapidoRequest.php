<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReporteRapidoRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:150'],
            'salvar_como' => ['required', 'in:rascunho,publicado'],
            'status_operacional' => ['required', 'string', 'max:100'],
            'primeiro_contato' => ['required', 'string', 'max:100'],
            'observacao' => ['required', 'string', 'max:500'],
            'documento' => ['nullable', 'string', 'max:100'],
            'tempo_parado' => ['nullable', 'string', 'max:20'],
            'segundo_contato' => ['nullable', 'string', 'max:100'],
            'data_hora_previsao' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do reporte é obrigatório.',
            'status_operacional.required' => 'O status operacional é obrigatório.',
            'primeiro_contato.required' => 'O 1º contato é obrigatório.',
            'observacao.required' => 'A observação é obrigatória.',
        ];
    }
}
