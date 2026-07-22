<?php

namespace App\Http\Requests;

use App\Enums\StatusItemDemanda;
use App\Enums\TipoDemanda;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandaRequest extends FormRequest
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
            'tipo_demanda' => ['nullable', Rule::enum(TipoDemanda::class)],
            'equipamento_id' => ['nullable', 'exists:equipamentos,id'],
            'documento_demanda' => ['nullable', 'string', 'max:100'],
            'data_hora_inicio_demanda' => ['nullable', 'date'],
            'data_hora_fim_demanda' => ['nullable', 'date', 'after_or_equal:data_hora_inicio_demanda'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Regra de negócio: o início pode ser dado com itens pendentes, mas o fim
     * só é permitido quando todos os itens já tiveram o status definido.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('data_hora_fim_demanda')) {
                return;
            }

            $demanda = $this->route('demanda');

            $pendentes = $demanda->itens()
                ->where(fn ($q) => $q->whereNull('status_item')
                    ->orWhere('status_item', StatusItemDemanda::Pendente->value))
                ->count();

            if ($pendentes > 0) {
                $validator->errors()->add(
                    'data_hora_fim_demanda',
                    "Defina o status de todos os itens antes de informar o fim da demanda ({$pendentes} item(ns) ainda pendente(s))."
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'data_hora_inicio_demanda' => 'início da demanda',
            'data_hora_fim_demanda' => 'fim da demanda',
        ];
    }
}
