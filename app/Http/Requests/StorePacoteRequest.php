<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePacoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->salao_id !== null;
    }

    public function rules(): array
    {
        return [
            'nome'          => ['required', 'string', 'max:120'],
            'sessoes'       => ['required', 'integer', 'min:1', 'max:100'],
            'validade_dias' => ['nullable', 'integer', 'min:1', 'max:730'],
            'preco'         => ['required', 'numeric', 'min:0'],
            'ativo'         => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'    => 'Informe o nome do pacote.',
            'sessoes.required' => 'Informe a quantidade de sessões.',
            'sessoes.min'      => 'O pacote precisa ter ao menos 1 sessão.',
            'preco.required'   => 'Informe o preço do pacote.',
        ];
    }
}
