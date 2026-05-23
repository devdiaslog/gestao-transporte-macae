<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateReporteRequest extends FormRequest
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
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.prefixo' => ['required', 'string', 'max:50'],
            'itens.*.status_operacional' => ['required', 'string', 'max:100'],
            'itens.*.primeiro_contato' => ['required', 'string', 'max:100'],
            'itens.*.documento' => ['nullable', 'string', 'max:100'],
            'itens.*.tempo_parado' => ['nullable', 'string', 'max:20'],
            'itens.*.data_hora_previsao' => ['nullable', 'string', 'max:30'],
            'itens.*.segundo_contato' => ['nullable', 'string', 'max:100'],
            'itens.*.observacao' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do reporte é obrigatório.',
            'itens.required' => 'Adicione pelo menos um veículo.',
            'itens.min' => 'Adicione pelo menos um veículo.',
            'itens.*.prefixo.required' => 'O prefixo é obrigatório.',
            'itens.*.status_operacional.required' => 'O status operacional é obrigatório.',
            'itens.*.primeiro_contato.required' => 'O 1º contato é obrigatório.',
        ];
    }
}
