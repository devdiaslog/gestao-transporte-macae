<?php

namespace App\Http\Requests;

use App\Enums\AtividadeCerca;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCercaRequest extends FormRequest
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
            'atividade' => ['nullable', new Enum(AtividadeCerca::class)],
            'poligono' => ['nullable', 'array'],
            'status' => ['required', 'boolean'],
            'tempo_minimo' => ['required', 'integer', 'min:1', 'max:1440'],
            'tempo_maximo' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $poligono = $this->input('poligono');

        if (is_string($poligono) && $poligono !== '') {
            $decoded = json_decode($poligono, true);
            $poligono = is_array($decoded) ? $decoded : null;
        } else {
            $poligono = null;
        }

        $this->merge([
            'status' => (bool) $this->input('status', true),
            'poligono' => $poligono,
        ]);
    }
}
