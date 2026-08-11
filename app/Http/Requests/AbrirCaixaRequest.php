<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbrirCaixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'saldo_inicial' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'observacao'    => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'saldo_inicial.required' => 'Informe o saldo inicial do caixa.',
            'saldo_inicial.min'      => 'O saldo inicial não pode ser negativo.',
        ];
    }
}
