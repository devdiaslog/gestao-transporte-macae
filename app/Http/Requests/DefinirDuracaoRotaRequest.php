<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DefinirDuracaoRotaRequest extends FormRequest
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
            'local_origem_norm' => ['required', 'string', 'max:255'],
            'local_destino_norm' => ['required', 'string', 'max:255'],
            'horas' => ['required', 'numeric', 'min:0.5', 'max:720'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'horas.required' => 'Informe quantas horas leva atender a rota.',
            'horas.min' => 'O tempo mínimo é meia hora.',
            'horas.max' => 'O tempo máximo é 720 horas (30 dias).',
        ];
    }
}
