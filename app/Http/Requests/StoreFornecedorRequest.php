<?php

namespace App\Http\Requests;

use App\Models\Fornecedor;
use Illuminate\Foundation\Http\FormRequest;

class StoreFornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Fornecedor::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nome'        => ['required', 'string', 'max:255'],
            'contato'     => ['nullable', 'string', 'max:255'],
            'telefone'    => ['nullable', 'string', 'max:30'],
            'email'       => ['nullable', 'email', 'max:255'],
            'documento'   => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'ativo'       => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do fornecedor.',
        ];
    }
}
