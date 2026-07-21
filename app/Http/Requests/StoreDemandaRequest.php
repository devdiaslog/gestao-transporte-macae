<?php

namespace App\Http\Requests;

use App\Enums\TipoDemanda;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandaRequest extends FormRequest
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
            'numero_demanda' => ['required', 'integer', 'digits_between:9,10', 'unique:demandas,numero_demanda'],
            'tipo_demanda' => ['nullable', Rule::enum(TipoDemanda::class)],
            'equipamento_id' => ['nullable', 'exists:equipamentos,id'],
            'documento_demanda' => ['nullable', 'string', 'max:100'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'numero_demanda.required' => 'O número da demanda é obrigatório.',
            'numero_demanda.integer' => 'O número da demanda deve ser um valor inteiro.',
            'numero_demanda.digits_between' => 'O número da demanda deve ter entre 9 e 10 dígitos.',
            'numero_demanda.unique' => 'Este número de demanda já está cadastrado.',
        ];
    }
}
