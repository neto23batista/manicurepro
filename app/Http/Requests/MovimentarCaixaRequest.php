<?php

namespace App\Http\Requests;

use App\Models\CaixaMovimentacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentarCaixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'tipo'      => ['required', 'string', Rule::in(CaixaMovimentacao::TIPOS)],
            'valor'     => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'descricao' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required'      => 'Selecione o tipo de movimentação.',
            'tipo.in'            => 'Tipo de movimentação inválido.',
            'valor.required'     => 'Informe o valor.',
            'valor.min'          => 'O valor deve ser maior que zero.',
            'descricao.required' => 'Informe a descrição.',
        ];
    }
}
