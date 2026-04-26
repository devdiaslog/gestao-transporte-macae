<?php

namespace App\Http\Requests;

use App\Models\Ocorrencia;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreOcorrenciaRequest extends FormRequest
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
            'id_veiculo' => [
                'required',
                'integer',
                'exists:equipamentos,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $aberta = Ocorrencia::where('id_veiculo', $value)
                        ->whereNull('data_hora_fim')
                        ->exists();

                    if ($aberta) {
                        $fail('Este veículo já possui uma ocorrência em aberto.');
                    }
                },
            ],
            'id_tipo' => ['required', 'integer', 'exists:tipos_ocorrencia,id_tipo'],
            'id_responsavel' => ['required', 'integer', 'exists:responsaveis,id_responsavel'],
            'id_justificativa' => ['required', 'integer', 'exists:justificativas,id_justificativa'],
            'data_hora_inicio' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $veiculo = $this->input('id_veiculo');
                    $fim = $this->input('data_hora_fim');

                    if (! $veiculo) {
                        return;
                    }

                    $inicio = Carbon::parse($value)->format('Y-m-d H:i:s');
                    $fimNorm = $fim ? Carbon::parse($fim)->format('Y-m-d H:i:s') : null;

                    $sobreposicao = Ocorrencia::where('id_veiculo', $veiculo)
                        ->where(function ($q) use ($inicio, $fimNorm): void {
                            // Existente termina depois do novo início (ou nunca termina)
                            $q->where(function ($q2) use ($inicio): void {
                                $q2->whereNull('data_hora_fim')
                                    ->orWhere('data_hora_fim', '>', $inicio);
                            });
                            // Existente começa antes do novo fim (ou novo fim é nulo = infinito)
                            if ($fimNorm) {
                                $q->where('data_hora_inicio', '<', $fimNorm);
                            }
                        })
                        ->exists();

                    if ($sobreposicao) {
                        $fail('Já existe uma ocorrência para este veículo no período informado.');
                    }
                },
            ],
            'data_hora_fim' => ['nullable', 'date', 'after_or_equal:data_hora_inicio'],
            'observacao' => ['nullable', 'string'],
            'documento' => ['nullable', 'string', 'max:100'],
            'numero_ro' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id_veiculo.required' => 'O veículo é obrigatório.',
            'id_tipo.required' => 'O tipo de ocorrência é obrigatório.',
            'id_responsavel.required' => 'O responsável é obrigatório.',
            'id_justificativa.required' => 'A justificativa é obrigatória.',
            'data_hora_inicio.required' => 'A data/hora de início é obrigatória.',
            'data_hora_fim.after_or_equal' => 'A data/hora de fim deve ser igual ou posterior ao início.',
        ];
    }
}
