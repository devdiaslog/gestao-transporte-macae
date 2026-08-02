<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportarDemandasApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Cada item usa os mesmos campos da planilha de importação. Campo ausente
     * não altera o dado existente; datas no formato dd.mm.aaaa (SAP) ou aaaa-mm-dd.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1', 'max:1000'],
            'itens.*.nota' => ['required'],
            'itens.*.criacao_data' => ['nullable', 'string'],
            'itens.*.criacao_hora' => ['nullable', 'string'],
            'itens.*.numero_rt' => ['required'],
            'itens.*.numero_item' => ['nullable'],
            'itens.*.subitem' => ['nullable'],
            'itens.*.tipo_demanda' => ['nullable', 'string'],
            'itens.*.local_origem' => ['nullable', 'string', 'max:255'],
            'itens.*.local_destino' => ['nullable', 'string', 'max:255'],
            'itens.*.descricao_local_retirada' => ['nullable', 'string', 'max:255'],
            'itens.*.descricao_item' => ['nullable', 'string', 'max:2000'],
            'itens.*.peso_total' => ['nullable', 'string', 'max:30'],
            'itens.*.altura' => ['nullable', 'string', 'max:30'],
            'itens.*.largura' => ['nullable', 'string', 'max:30'],
            'itens.*.comprimento' => ['nullable', 'string', 'max:30'],
            'itens.*.status_item' => ['nullable'],
            'itens.*.prazo_data' => ['nullable', 'string'],
            'itens.*.prazo_hora' => ['nullable', 'string'],
            'itens.*.entrega_data' => ['nullable', 'string'],
            'itens.*.entrega_hora' => ['nullable', 'string'],
            'itens.*.observacao' => ['nullable', 'string', 'max:5000'],
            'itens.*.equipamento' => ['nullable', 'string', 'max:255'],

            // Dados da RT, para o item que entra direto como programado sem
            // ter passado pela importação de itens liberados.
            'itens.*.criacao_rt_data' => ['nullable', 'string'],
            'itens.*.criacao_rt_hora' => ['nullable', 'string'],
            'itens.*.liberacao_data' => ['nullable', 'string'],
            'itens.*.liberacao_hora' => ['nullable', 'string'],
            'itens.*.doc_unitizacao_superior' => ['nullable', 'string', 'max:255'],
            'itens.*.descricao_contentor' => ['nullable', 'string', 'max:2000'],
            'itens.*.comprimento_embalagem' => ['nullable', 'string', 'max:30'],
            'itens.*.largura_embalagem' => ['nullable', 'string', 'max:30'],
            'itens.*.altura_embalagem' => ['nullable', 'string', 'max:30'],
            'itens.*.grupo_planejamento' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Envie ao menos um item em "itens".',
            'itens.*.nota.required' => 'Cada item precisa do número da demanda (nota).',
            'itens.*.numero_rt.required' => 'Cada item precisa do número da RT (numero_rt).',
        ];
    }
}
