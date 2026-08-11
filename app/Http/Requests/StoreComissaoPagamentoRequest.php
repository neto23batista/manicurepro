<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComissaoPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'manicure_id' => ['required', 'integer', 'exists:manicures,id'],
            'data_inicio' => ['required', 'date'],
            'data_fim'    => ['required', 'date', 'after_or_equal:data_inicio'],
            'periodo'     => ['nullable', 'string', 'in:hoje,semana,mes,custom'],
            'observacao'  => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'manicure_id.required'    => 'Selecione o profissional.',
            'manicure_id.exists'      => 'Profissional inválido.',
            'data_inicio.required'    => 'Informe o início do período.',
            'data_fim.required'       => 'Informe o fim do período.',
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à inicial.',
        ];
    }
}
