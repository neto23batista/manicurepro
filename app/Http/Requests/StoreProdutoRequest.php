<?php

namespace App\Http\Requests;

use App\Models\Produto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Produto::class) ?? false;
    }

    public function rules(): array
    {
        $salaoId = (int) ($this->user()->salao_id ?? 0);

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
            'fornecedor_id'  => [
                'nullable',
                Rule::exists('fornecedores', 'id')->where(fn ($q) => $q->where('salao_id', $salaoId)->where('ativo', true)),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('estoque_minimo')) {
            $this->merge([
                'estoque_minimo' => config('manicure.estoque.minimo_padrao', 1),
            ]);
        }

        if (! $this->filled('fornecedor_id')) {
            $this->merge(['fornecedor_id' => null]);
        }
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do produto.',
        ];
    }
}
