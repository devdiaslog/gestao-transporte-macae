<?php

namespace App\Http\Requests;

use App\Models\Etapa;
use App\Models\TipoEtapa;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateEtapaRequest extends FormRequest
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
            'tipo_etapa_id' => ['required', 'integer', 'exists:tipo_etapas,id'],
            'cerca_id' => ['nullable', 'integer', 'exists:cercas,id', $this->validarCercaObrigatoria()],
            'motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],
            'documento' => ['nullable', 'string', 'max:100'],
            'data_hora_inicio' => [
                'required',
                'date',
                'before_or_equal:now',
                $this->validarSemSobreposicao(),
            ],
            'data_hora_fim' => [
                'nullable',
                'date',
                'after:data_hora_inicio',
                $this->validarFinalizacaoViaFluxoCorreto(),
                $this->validarReaberturaPermitida(),
            ],
            'observacao' => ['nullable', 'string', 'max:2000'],
            'motivo_longa_duracao' => ['nullable', 'string', 'max:500', $this->validarMotivoLongaDuracao()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tipo_etapa_id.required' => 'O tipo de etapa é obrigatório.',
            'data_hora_inicio.required' => 'A data/hora de início é obrigatória.',
            'data_hora_inicio.before_or_equal' => 'A data/hora de início não pode ser no futuro.',
            'data_hora_fim.after' => 'A data/hora de fim deve ser posterior ao início.',
        ];
    }

    /** Exige justificativa quando a duração da etapa ultrapassa 24 horas. */
    private function validarMotivoLongaDuracao(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $inicio = $this->input('data_hora_inicio');
            $fim = $this->input('data_hora_fim');

            if (! $inicio || ! $fim) {
                return;
            }

            if (Carbon::parse($fim)->diffInHours(Carbon::parse($inicio)) > 24 && empty($value)) {
                $fail('A etapa tem duração superior a 24 horas. Informe o motivo.');
            }
        };
    }

    /** Exige a cerca quando o tipo de etapa selecionado a torna obrigatória. */
    private function validarCercaObrigatoria(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $tipo = TipoEtapa::find($this->input('tipo_etapa_id'));

            if ($tipo?->necessita_cerca && empty($value)) {
                $fail('Este tipo de etapa exige o preenchimento da cerca.');
            }
        };
    }

    /**
     * Impede que uma etapa em aberto seja finalizada pela edição direta.
     * Finalização deve passar pelo fluxo correto (etapas.finalizar), que obriga
     * o registro da próxima etapa.
     */
    private function validarFinalizacaoViaFluxoCorreto(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (empty($value)) {
                return;
            }

            /** @var Etapa $etapa */
            $etapa = $this->route('etapa');

            if (is_null($etapa->data_hora_fim)) {
                $fail('Para finalizar uma etapa é necessário usar o botão "Finalizar", pois a próxima etapa deve ser registrada ao mesmo tempo.');
            }
        };
    }

    /**
     * Bloqueia se o novo início sobrepõe o intervalo de outra etapa do mesmo
     * veículo (excluindo a própria etapa que está sendo editada).
     */
    private function validarSemSobreposicao(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var Etapa $etapa */
            $etapa = $this->route('etapa');

            $novoInicio = Carbon::parse($value);
            $novoFim = $this->filled('data_hora_fim')
                ? Carbon::parse($this->input('data_hora_fim'))
                : null;

            // Verifica sobreposição com qualquer outra etapa finalizada do veículo
            $sobreposicao = Etapa::query()
                ->where('equipamento_id', $etapa->equipamento_id)
                ->where('id', '!=', $etapa->id)
                ->whereNotNull('data_hora_fim')
                ->where(function ($q) use ($novoInicio, $novoFim): void {
                    // Novo início cai dentro de uma etapa existente
                    $q->where('data_hora_inicio', '<=', $novoInicio)
                        ->where('data_hora_fim', '>', $novoInicio);

                    // Ou novo fim cai dentro de uma etapa existente
                    if ($novoFim) {
                        $q->orWhere(function ($q2) use ($novoFim): void {
                            $q2->where('data_hora_inicio', '<', $novoFim)
                                ->where('data_hora_fim', '>=', $novoFim);
                        });
                    }
                })
                ->exists();

            if ($sobreposicao) {
                $fail('O intervalo desta etapa se sobrepõe com outra etapa já registrada para este veículo.');
            }
        };
    }

    /**
     * Se o data_hora_fim está sendo removido (reabrindo a etapa), verifica se
     * não existe nenhuma etapa posterior registrada para o mesmo veículo.
     */
    private function validarReaberturaPermitida(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! empty($value)) {
                return; // Está finalizando, não reabrindo — sem restrição aqui
            }

            /** @var Etapa $etapa */
            $etapa = $this->route('etapa');

            $temPosterior = Etapa::query()
                ->where('equipamento_id', $etapa->equipamento_id)
                ->where('id', '!=', $etapa->id)
                ->where('data_hora_inicio', '>', $etapa->data_hora_inicio)
                ->exists();

            if ($temPosterior) {
                $fail('Não é possível reabrir esta etapa pois existem etapas posteriores registradas para este veículo.');
            }
        };
    }
}
