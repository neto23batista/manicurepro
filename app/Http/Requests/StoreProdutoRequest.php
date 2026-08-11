<?php

namespace App\Http\Requests;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Produto::class) ?? false;
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

    protected function prepareForValidation(): void
    {
        if (! $this->filled('estoque_minimo')) {
            $this->merge([
                'estoque_minimo' => config('manicure.estoque.minimo_padrao', 1),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do produto.',
        ];
    }
}
