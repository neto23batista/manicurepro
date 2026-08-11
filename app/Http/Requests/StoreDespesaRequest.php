<?php

namespace App\Http\Requests;

use App\Models\Despesa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDespesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'descricao'  => ['required', 'string', 'max:255'],
            'categoria'  => ['required', 'string', Rule::in(array_keys(Despesa::CATEGORIAS))],
            'fornecedor' => ['nullable', 'string', 'max:255'],
            'valor'      => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'vencimento' => ['required', 'date'],
            'recorrente' => ['nullable', 'boolean'],
            'pago'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'descricao.required'  => 'Informe a descrição da despesa.',
            'categoria.required'  => 'Selecione a categoria.',
            'categoria.in'        => 'Categoria inválida.',
            'valor.required'      => 'Informe o valor.',
            'valor.min'           => 'O valor deve ser maior que zero.',
            'vencimento.required' => 'Informe a data de vencimento.',
        ];
    }
}
