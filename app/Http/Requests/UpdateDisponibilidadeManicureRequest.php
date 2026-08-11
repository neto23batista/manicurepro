<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDisponibilidadeManicureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->isDono() || $user->isAtendente() || $user->isSuperAdmin()) {
            return true;
        }

        return $user->isManicure() && $user->manicure !== null;
    }

    public function rules(): array
    {
        return [
            'dias'                    => ['required', 'array'],
            'dias.*.ativo'            => ['sometimes', 'boolean'],
            'dias.*.hora_inicio'      => ['nullable', 'date_format:H:i'],
            'dias.*.hora_fim'         => ['nullable', 'date_format:H:i'],
            'dias.*.pausa_inicio'     => ['nullable', 'date_format:H:i'],
            'dias.*.pausa_fim'        => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach ((array) $this->input('dias', []) as $dia => $dados) {
                $ativo = filter_var($dados['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if (! $ativo) {
                    continue;
                }

                $inicio = $dados['hora_inicio'] ?? null;
                $fim = $dados['hora_fim'] ?? null;
                if (! $inicio || ! $fim) {
                    $v->errors()->add("dias.$dia.hora_inicio", 'Informe início e fim do expediente.');
                    continue;
                }
                if ($fim <= $inicio) {
                    $v->errors()->add("dias.$dia.hora_fim", 'O fim deve ser após o início.');
                }

                $pausaIni = $dados['pausa_inicio'] ?? null;
                $pausaFim = $dados['pausa_fim'] ?? null;
                if ($pausaIni || $pausaFim) {
                    if (! $pausaIni || ! $pausaFim) {
                        $v->errors()->add("dias.$dia.pausa_inicio", 'Informe início e fim da pausa.');
                    } elseif ($pausaFim <= $pausaIni) {
                        $v->errors()->add("dias.$dia.pausa_fim", 'O fim da pausa deve ser após o início.');
                    } elseif ($pausaIni < $inicio || $pausaFim > $fim) {
                        $v->errors()->add("dias.$dia.pausa_inicio", 'A pausa deve estar dentro do expediente.');
                    }
                }
            }
        });
    }
}
