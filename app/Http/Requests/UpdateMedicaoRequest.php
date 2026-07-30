<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicaoRequest extends FormRequest
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
            'nome_medicao' => ['required', 'string', 'max:255'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome_medicao.required' => 'Informe o nome da medição.',
            'data_inicio.required' => 'Informe a data de início.',
            'data_fim.required' => 'Informe a data de fim.',
            'data_fim.after_or_equal' => 'A data de fim deve ser igual ou posterior à de início.',
        ];
    }
}
