<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigSalaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->salao_id !== null;
    }

    public function rules(): array
    {
        return [
            'cor_primaria'                => ['required', 'hex_color', 'size:7'],
            'permitir_agendamento_online' => ['sometimes', 'boolean'],
            'intervalo_agendamento'       => ['required', 'integer', 'min:5', 'max:240'],
            'antecedencia_minima'         => ['required', 'integer', 'min:0', 'max:30'],
            'antecedencia_maxima'         => ['required', 'integer', 'min:1', 'max:365'],
            'cancelamento_prazo'          => ['required', 'integer', 'min:0', 'max:72'],
            'fidelidade_ativo'            => ['sometimes', 'boolean'],
            'pontos_por_real'             => ['required', 'integer', 'min:0'],
            'pontos_para_desconto'        => ['required', 'integer', 'min:0'],
            'valor_desconto_pontos'       => ['required', 'numeric', 'min:0'],
            'notificar_email'             => ['sometimes', 'boolean'],
            'notificar_whatsapp'          => ['sometimes', 'boolean'],
            'lembrete_horas'              => ['required', 'integer', 'min:1', 'max:168'],
            'limite_alerta_no_show'       => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'cor_primaria.hex_color' => 'A cor primária deve ser um hexadecimal válido (ex.: #e91e8c).',
            'cor_primaria.size'      => 'A cor primária deve ter o formato #RRGGBB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $cor = $this->input('cor_primaria');
        if (is_string($cor) && $cor !== '' && ! str_starts_with($cor, '#')) {
            $this->merge(['cor_primaria' => '#'.$cor]);
        }
    }
}
