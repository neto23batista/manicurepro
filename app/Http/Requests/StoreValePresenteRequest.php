<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreValePresenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'atendente', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'valor'             => ['required', 'numeric', 'min:1', 'max:99999.99'],
            'forma'             => ['nullable', 'in:dinheiro,cartao_credito,cartao_debito,pix,transferencia'],
            'comprador_nome'    => ['nullable', 'string', 'max:255'],
            'comprador_contato' => ['nullable', 'string', 'max:255'],
            'beneficiario_nome' => ['nullable', 'string', 'max:255'],
            'mensagem'          => ['nullable', 'string', 'max:500'],
            'validade'          => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor.required' => 'Informe o valor do vale.',
            'valor.min'      => 'O valor mínimo é R$ 1,00.',
        ];
    }
}
