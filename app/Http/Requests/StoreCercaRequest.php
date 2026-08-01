<?php

namespace App\Http\Requests;

use App\Enums\AtividadeCerca;
use App\Rules\PoligonoValido;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCercaRequest extends FormRequest
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
            'poligono' => ['required', 'array', new PoligonoValido],
            'status' => ['required', 'boolean'],
            'tempo_minimo' => ['required', 'integer', 'min:1', 'max:1440'],
            'tempo_maximo' => ['required', 'integer', 'min:1', 'max:1440', 'gte:tempo_minimo'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'poligono.required' => 'Desenhe a área da cerca no mapa antes de salvar.',
            'tempo_maximo.gte' => 'O tempo máximo deve ser maior ou igual ao tempo mínimo.',
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
