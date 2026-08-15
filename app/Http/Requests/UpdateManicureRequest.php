<?php

namespace App\Http\Requests;

use App\Models\Salao;
use Illuminate\Foundation\Http\FormRequest;

class UpdateManicureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'dono'], true);
    }

    protected function prepareForValidation(): void
    {
        // Instalação single-tenant: o salão é sempre o principal, definido no servidor.
        $this->merge(['salao_id' => Salao::principalId()]);
    }

    public function rules(): array
    {
        return [
            'salao_id' => ['required', 'exists:saloes,id'],
            'nome'     => ['required', 'string', 'max:255'],
            'email'    => ['nullable', 'email'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'bio'      => ['nullable', 'string'],
            'comissao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ativo'    => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'foto'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
