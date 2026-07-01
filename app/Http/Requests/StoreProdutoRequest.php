<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'atendente', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'nome'           => ['required', 'string', 'max:255'],
            'codigo'         => ['nullable', 'string', 'max:50'],
            'marca'          => ['nullable', 'string', 'max:255'],
            'descricao'      => ['nullable', 'string', 'max:1000'],
            'preco_custo'    => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'preco_venda'    => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'estoque_atual'  => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'unidade'        => ['nullable', 'string', 'max:20'],
            'ativo'          => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do produto.',
        ];
    }
}
