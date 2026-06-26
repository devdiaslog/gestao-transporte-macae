<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAlertaRequest extends FormRequest
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
            'lembrete' => ['required', 'string', 'max:500'],
            'data_hora_alerta' => ['required', 'date', 'after:now'],
            'para_todos' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'lembrete.required' => 'Descreva o lembrete do alerta.',
            'data_hora_alerta.required' => 'Informe a data e hora do alerta.',
            'data_hora_alerta.after' => 'O alerta deve ser agendado para um momento futuro.',
        ];
    }
}
