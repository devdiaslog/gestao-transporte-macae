<?php

namespace App\Http\Requests;

use App\Enums\StatusItemDemanda;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandaItemRequest extends FormRequest
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
        $demanda = $this->route('demanda');

        return [
            'numero_rt' => [
                'required', 'string', 'max:50',
                Rule::unique('demanda_itens')
                    ->where(fn ($q) => $q
                        ->where('demanda_id', $demanda->id)
                        ->where('numero_item', $this->input('numero_item'))
                        ->where('subitem', $this->input('subitem'))),
            ],
            'numero_item' => ['required', 'string', 'max:50'],
            'subitem' => ['nullable', 'string', 'max:50'],
            'local_origem' => ['nullable', 'string', 'max:255'],
            'local_destino' => ['nullable', 'string', 'max:255'],
            'descricao_local_retirada' => ['nullable', 'string', 'max:255'],
            'descricao_item' => ['nullable', 'string', 'max:2000'],
            'status_item' => ['nullable', Rule::enum(StatusItemDemanda::class)],
            'prazo_item' => ['nullable', 'date'],
            'data_hora_entrega' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_rt.unique' => 'Já existe um item nesta demanda com essa combinação de RT, item e subitem.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'numero_rt' => 'RT',
            'numero_item' => 'item',
            'subitem' => 'subitem',
            'local_origem' => 'local de origem',
            'local_destino' => 'local de destino',
            'descricao_local_retirada' => 'descrição do local de retirada',
            'descricao_item' => 'descrição do item',
            'status_item' => 'status do item',
            'prazo_item' => 'prazo do item',
            'data_hora_entrega' => 'data/hora de entrega',
        ];
    }
}
