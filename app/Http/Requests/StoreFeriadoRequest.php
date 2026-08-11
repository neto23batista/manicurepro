<?php

namespace App\Http\Requests;

use App\Models\Feriado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFeriadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Feriado::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome'        => ['required', 'string', 'max:255'],
            'mes'         => ['required', 'integer', 'min:1', 'max:12'],
            'dia'         => ['required', 'integer', 'min:1', 'max:31'],
            'dia_todo'    => ['sometimes', 'boolean'],
            'hora_inicio' => ['nullable', 'required_if:dia_todo,0', 'date_format:H:i'],
            'hora_fim'    => ['nullable', 'required_if:dia_todo,0', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $mes = (int) $this->input('mes');
            $dia = (int) $this->input('dia');
            if ($mes < 1 || $mes > 12 || $dia < 1) {
                return;
            }
            // Valida dia real no ano bissexto (29/02 ok)
            if (! checkdate($mes, $dia, 2024)) {
                $v->errors()->add('dia', 'Data inválida para o mês informado.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do feriado.',
            'mes.required'  => 'Informe o mês.',
            'dia.required'  => 'Informe o dia.',
        ];
    }
}
