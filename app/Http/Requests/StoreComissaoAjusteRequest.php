<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComissaoAjusteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isDono() || $user->isSuperAdmin());
    }

    public function rules(): array
    {
        return [
            'manicure_id' => ['required', 'integer', 'exists:manicures,id'],
            'data_inicio' => ['required', 'date'],
            'data_fim'    => ['required', 'date', 'after_or_equal:data_inicio'],
            'valor'       => ['required', 'numeric', 'not_in:0', 'min:-99999.99', 'max:99999.99'],
            'motivo'      => ['nullable', 'string', 'max:255'],
            'periodo'     => ['nullable', 'string', 'in:hoje,semana,mes,custom'],
        ];
    }

    public function messages(): array
    {
        return [
            'manicure_id.required' => 'Selecione o profissional.',
            'valor.required'       => 'Informe o valor do ajuste.',
            'valor.not_in'         => 'O valor do ajuste não pode ser zero.',
        ];
    }
}
