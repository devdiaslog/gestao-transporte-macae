<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportarTrechosSapRequest extends FormRequest
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
            /**
             * O .xlsx é um zip por dentro e o fileinfo o reporta como
             * "application/zip"; por isso a extensão define o formato e o
             * mimetype só barra o que não tem cara de planilha.
             */
            'arquivo' => [
                'required',
                'file',
                'extensions:xlsx,xls',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,application/zip,application/octet-stream',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'arquivo.required' => 'Selecione a planilha para importar.',
            'arquivo.extensions' => 'A planilha deve estar em formato .xlsx ou .xls.',
            'arquivo.mimetypes' => 'O arquivo enviado não é uma planilha.',
            'arquivo.max' => 'A planilha não pode ultrapassar 10 MB.',
        ];
    }
}
