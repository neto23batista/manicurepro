<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente
            ? ($this->user()?->can('update', $cliente) ?? false)
            : false;
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
            'notas_unhas'      => ['nullable', 'string', 'max:2000'],
            'cores_preferidas' => ['nullable', 'string', 'max:500'],
            'contraindicacoes' => ['nullable', 'string', 'max:1000'],
            'ultima_formula'   => ['nullable', 'string', 'max:2000'],
            'ativo'            => ['sometimes', 'boolean'],
        ];
    }
}
