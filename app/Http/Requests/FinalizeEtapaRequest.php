<?php

namespace App\Http\Requests;

use App\Models\Etapa;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class FinalizeEtapaRequest extends FormRequest
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
            // ── Encerramento da etapa atual ──────────────────────────────────────
            'data_hora_fim' => [
                'required',
                'date',
                'before_or_equal:now',
                $this->validarFimAposInicio(),
            ],

            // ── Próxima etapa ────────────────────────────────────────────────────
            'proxima_tipo_etapa_id' => ['required', 'integer', 'exists:tipo_etapas,id'],
            'proxima_cerca_id' => ['required', 'integer', 'exists:cercas,id'],
            'proxima_motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],
            'proxima_documento' => ['nullable', 'string', 'max:100'],
            'proxima_data_hora_inicio' => [
                'required',
                'date',
                'before_or_equal:now',
                $this->validarProximaAposFim(),
            ],
            'proxima_observacao' => ['nullable', 'string', 'max:2000'],
            'motivo_longa_duracao' => ['nullable', 'string', 'max:500', $this->validarMotivoLongaDuracao()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'data_hora_fim.required' => 'Informe o horário de encerramento da etapa.',
            'data_hora_fim.before_or_equal' => 'O encerramento não pode ser no futuro.',
            'proxima_tipo_etapa_id.required' => 'Informe o tipo da próxima etapa.',
            'proxima_cerca_id.required' => 'Informe a cerca da próxima etapa.',
            'proxima_data_hora_inicio.required' => 'Informe o início da próxima etapa.',
            'proxima_data_hora_inicio.before_or_equal' => 'O início da próxima etapa não pode ser no futuro.',
        ];
    }

    /** Exige justificativa quando a duração da etapa (início atual → fim informado) ultrapassa 24 horas. */
    private function validarMotivoLongaDuracao(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var Etapa $etapa */
            $etapa = $this->route('etapa');
            $fim = $this->input('data_hora_fim');

            if (! $fim) {
                return;
            }

            if (Carbon::parse($fim)->diffInHours($etapa->data_hora_inicio) > 24 && empty($value)) {
                $fail('A etapa tem duração superior a 24 horas. Informe o motivo.');
            }
        };
    }

    /** Fim deve ser após o início da etapa atual. */
    private function validarFimAposInicio(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var Etapa $etapa */
            $etapa = $this->route('etapa');

            if (Carbon::parse($value)->lte($etapa->data_hora_inicio)) {
                $fail(
                    'O encerramento deve ser posterior ao início da etapa ('
                    .$etapa->data_hora_inicio->format('d/m/Y H:i').').'
                );
            }
        };
    }

    /** Início da próxima etapa deve ser >= fim da atual. */
    private function validarProximaAposFim(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $fim = $this->filled('data_hora_fim')
                ? Carbon::parse($this->input('data_hora_fim'))
                : null;

            if ($fim && Carbon::parse($value)->lt($fim)) {
                $fail(
                    'O início da próxima etapa ('
                    .Carbon::parse($value)->format('H:i')
                    .') não pode ser anterior ao encerramento da etapa atual ('
                    .$fim->format('H:i').').'
                );
            }
        };
    }
}
