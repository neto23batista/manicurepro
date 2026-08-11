<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fornecedor = $this->route('fornecedor');

        return $fornecedor
            ? ($this->user()?->can('update', $fornecedor) ?? false)
            : false;
    }

    public function rules(): array
    {
        return [
            'nome'         => ['required', 'string', 'max:255'],
            'contato'      => ['nullable', 'string', 'max:255'],
            'telefone'     => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'documento'    => ['nullable', 'string', 'max:30'],
            'observacoes'  => ['nullable', 'string', 'max:2000'],
            'ativo'        => ['sometimes', 'boolean'],
        ];
    }
}
