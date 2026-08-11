<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FecharCaixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'saldo_final_informado' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'observacao'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'saldo_final_informado.required' => 'Informe o saldo em caixa na contagem.',
            'saldo_final_informado.min'      => 'O saldo informado não pode ser negativo.',
        ];
    }
}
