<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', Rule::unique('locais', 'nome')->ignore($this->route('local'))],
            'ativo' => ['boolean'],
            'precisa_agendamento' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'nome.unique' => 'Já existe um local com este nome.',
            'nome.max' => 'O nome não pode ultrapassar 255 caracteres.',
        ];
    }
}
