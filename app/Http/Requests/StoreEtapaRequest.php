<?php

namespace App\Http\Requests;

use App\Models\Etapa;
use App\Models\TipoEtapa;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreEtapaRequest extends FormRequest
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
            'equipamento_id' => ['required', 'integer', 'exists:equipamentos,id'],
            'tipo_etapa_id' => ['required', 'integer', 'exists:tipo_etapas,id', $this->validarTipoLocalUnico()],
            'cerca_id' => ['nullable', 'integer', 'exists:cercas,id', $this->validarCercaObrigatoria()],
            'motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],
            'documento' => ['nullable', 'string', 'max:100'],
            'data_hora_inicio' => [
                'required',
                'date',
                'before_or_equal:now',
                $this->validarSemEtapaAberta(),
                $this->validarSemSobreposicaoTemporal(),
                $this->validarInicioAposUltimaEtapa(),
            ],
            'data_hora_fim' => ['nullable', 'date', 'after:data_hora_inicio'],
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

    /** Bloqueia se o veículo já possui uma etapa em aberto. */
    private function validarSemEtapaAberta(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $aberta = Etapa::query()
                ->where('equipamento_id', $this->input('equipamento_id'))
                ->whereNull('data_hora_fim')
                ->exists();

            if ($aberta) {
                $fail('Este veículo já possui uma etapa em aberto. Finalize-a antes de registrar uma nova.');
            }
        };
    }

    /**
     * Bloqueia se a última etapa do veículo já possui o mesmo tipo e cerca,
     * evitando duplicação consecutiva acidental.
     */
    private function validarTipoLocalUnico(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $ultima = Etapa::query()
                ->where('equipamento_id', $this->input('equipamento_id'))
                ->latest('data_hora_inicio')
                ->first(['tipo_etapa_id', 'cerca_id']);

            if (
                $ultima
                && (int) $ultima->tipo_etapa_id === (int) $value
                && (int) $ultima->cerca_id === (int) $this->input('cerca_id')
            ) {
                $fail('A etapa anterior já possui o mesmo tipo e cerca. Verifique se não é um registro duplicado.');
            }
        };
    }

    /**
     * Bloqueia se o início informado está contido dentro do intervalo de qualquer
     * etapa já finalizada do veículo.
     * Ex.: Etapa A 07:00–10:00 → início 08:00 é inválido.
     */
    private function validarSemSobreposicaoTemporal(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $novoInicio = Carbon::parse($value);

            $contida = Etapa::query()
                ->where('equipamento_id', $this->input('equipamento_id'))
                ->whereNotNull('data_hora_fim')
                ->where('data_hora_inicio', '<=', $novoInicio)
                ->where('data_hora_fim', '>', $novoInicio)
                ->first(['data_hora_inicio', 'data_hora_fim']);

            if ($contida) {
                $fail(
                    'O início informado ('
                    .$novoInicio->format('H:i')
                    .') está dentro do intervalo de uma etapa já registrada ('
                    .Carbon::parse($contida->data_hora_inicio)->format('H:i')
                    .'–'
                    .Carbon::parse($contida->data_hora_fim)->format('H:i')
                    .').'
                );
            }
        };
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

    /** Bloqueia se o início é anterior ao fim da última etapa finalizada do veículo. */
    private function validarInicioAposUltimaEtapa(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $ultimaFim = Etapa::query()
                ->where('equipamento_id', $this->input('equipamento_id'))
                ->whereNotNull('data_hora_fim')
                ->latest('data_hora_fim')
                ->value('data_hora_fim');

            if ($ultimaFim && Carbon::parse($value)->lt(Carbon::parse($ultimaFim))) {
                $fail(
                    'O início não pode ser anterior ao fim da última etapa ('
                    .Carbon::parse($ultimaFim)->format('d/m/Y H:i')
                    .').'
                );
            }
        };
    }
}
