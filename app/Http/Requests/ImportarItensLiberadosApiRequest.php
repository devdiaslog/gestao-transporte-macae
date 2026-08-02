<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportarItensLiberadosApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Cada item usa os mesmos campos da planilha de itens liberados. Campo
     * ausente não altera o dado existente; datas no formato dd.mm.aaaa (SAP)
     * ou aaaa-mm-dd.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'itens' => ['required', 'array', 'min:1', 'max:1000'],
            'itens.*.numero_rt' => ['required'],
            'itens.*.numero_item' => ['required'],
            'itens.*.subitem' => ['nullable'],

            'itens.*.criacao_data' => ['nullable', 'string'],
            'itens.*.criacao_hora' => ['nullable', 'string'],
            'itens.*.liberacao_data' => ['nullable', 'string'],
            'itens.*.liberacao_hora' => ['nullable', 'string'],
            'itens.*.prazo_data' => ['nullable', 'string'],
            'itens.*.prazo_hora' => ['nullable', 'string'],

            'itens.*.local_origem' => ['nullable', 'string', 'max:255'],
            'itens.*.local_destino' => ['nullable', 'string', 'max:255'],
            'itens.*.descricao_local_retirada' => ['nullable', 'string', 'max:255'],
            'itens.*.descricao_item' => ['nullable', 'string', 'max:2000'],

            'itens.*.peso_total' => ['nullable', 'string', 'max:30'],
            'itens.*.altura' => ['nullable', 'string', 'max:30'],
            'itens.*.largura' => ['nullable', 'string', 'max:30'],
            'itens.*.comprimento' => ['nullable', 'string', 'max:30'],

            'itens.*.doc_unitizacao_superior' => ['nullable', 'string', 'max:255'],
            'itens.*.grupo_planejamento' => ['nullable', 'string', 'max:255'],
            'itens.*.status_sap' => ['nullable', 'string', 'max:10'],

            /**
             * Só marque ausentes quando o envio for o conjunto completo dos
             * itens liberados: itens fora da carga são sinalizados para
             * conferência do operador.
             */
            'marcar_ausentes' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'itens.required' => 'Envie ao menos um item em "itens".',
            'itens.*.numero_rt.required' => 'Cada item precisa do número da RT (numero_rt).',
            'itens.*.numero_item.required' => 'Cada item precisa do número do item (numero_item).',
        ];
    }
}
