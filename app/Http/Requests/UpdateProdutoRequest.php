<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $produto = $this->route('produto');

        return $produto
            ? ($this->user()?->can('update', $produto) ?? false)
            : false;
    }

    public function rules(): array
    {
        $salaoId = (int) ($this->user()?->salao_id ?? 0);

        // O estoque não é alterado por aqui — usa-se a movimentação de estoque.
        return [
            'nome'           => ['required', 'string', 'max:255'],
            'codigo'         => ['nullable', 'string', 'max:50'],
            'marca'          => ['nullable', 'string', 'max:255'],
            'descricao'      => ['nullable', 'string', 'max:1000'],
            'preco_custo'    => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'preco_venda'    => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'unidade'        => ['nullable', 'string', 'max:20'],
            'ativo'          => ['sometimes', 'boolean'],
            'fornecedor_id'  => [
                'nullable',
                Rule::exists('fornecedores', 'id')->where(fn ($q) => $q->where('salao_id', $salaoId)->where('ativo', true)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('fornecedor_id')) {
            $this->merge(['fornecedor_id' => null]);
        }
    }
}
