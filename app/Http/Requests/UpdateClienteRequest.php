<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->salao_id !== null;
    }

    public function rules(): array
    {
        return [
            'nome'             => ['required', 'string', 'max:255'],
            'email'            => ['nullable', 'email', 'max:255'],
            'telefone'         => ['nullable', 'string', 'max:20'],
            'cpf'              => ['nullable', 'string', 'max:14'],
            'data_nascimento'  => ['nullable', 'date', 'before:today'],
            'endereco'         => ['nullable', 'string', 'max:500'],
            'observacoes'      => ['nullable', 'string', 'max:1000'],
            'alergias'         => ['nullable', 'string', 'max:500'],
            'ativo'            => ['sometimes', 'boolean'],
        ];
    }
}
