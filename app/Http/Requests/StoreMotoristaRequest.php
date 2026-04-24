<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMotoristaRequest extends FormRequest
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
            'matricula' => ['required', 'string', 'max:50', 'unique:motoristas,matricula'],
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:14', 'unique:motoristas,cpf'],
            'cargo' => ['required', 'string', 'max:100'],
            'status' => ['boolean'],

            'contatos' => ['nullable', 'array'],
            'contatos.*.telefone' => ['nullable', 'string', 'max:20'],
            'contatos.*.email' => ['nullable', 'email', 'max:255'],
            'contatos.*.status' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'matricula.required' => 'A matrícula é obrigatória.',
            'matricula.unique' => 'Esta matrícula já está em uso.',
            'nome.required' => 'O nome é obrigatório.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'cargo.required' => 'O cargo é obrigatório.',
            'contatos.*.email' => 'Informe um e-mail válido.',
        ];
    }
}
