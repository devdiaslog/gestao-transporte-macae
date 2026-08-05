<?php

namespace App\Http\Requests;

use App\Enums\PrazoPadrao;
use App\Models\TrechoSap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrechoSapRequest extends FormRequest
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
            'origem_sap' => ['required', 'string', 'max:255'],
            'destino_sap' => ['required', 'string', 'max:255'],
            'km_trecho' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'prazo_horas_normal' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'prazo_horas_expresso' => ['nullable', 'integer', 'min:1', 'max:8760'],
            'prazo_padrao' => ['required', Rule::enum(PrazoPadrao::class)],
        ];
    }

    /**
     * A unicidade é da chave canônica, não do par digitado: "ARM-MACAE" e
     * "ARM MACAÉ" são o mesmo trecho e não podem coexistir.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'chave_origem_destino' => TrechoSap::chaveDe(
                $this->input('origem_sap'),
                $this->input('destino_sap'),
            ),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $chave = TrechoSap::chaveDe($this->input('origem_sap'), $this->input('destino_sap'));

                $existe = TrechoSap::where('chave_origem_destino', $chave)
                    ->when($this->route('trechos_sap'), fn ($q, $trecho) => $q->whereKeyNot($trecho->id))
                    ->exists();

                if ($existe) {
                    $validator->errors()->add('origem_sap', "O trecho {$chave} já está cadastrado.");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'origem_sap.required' => 'Informe a origem.',
            'destino_sap.required' => 'Informe o destino.',
            'prazo_padrao.required' => 'Escolha qual prazo vale por padrão.',
        ];
    }
}
