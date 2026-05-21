<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReporteRequest extends FormRequest
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
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.prefixo' => ['nullable', 'string', 'max:50'],
            'itens.*.status_operacional' => ['nullable', 'string', 'max:100'],
            'itens.*.tempo_parado' => ['nullable', 'string', 'max:20'],
            'itens.*.observacao' => ['nullable', 'string', 'max:500'],
        ];
    }
}
