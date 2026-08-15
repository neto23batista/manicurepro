<?php

namespace App\Http\Requests;

use App\Models\Salao;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Instalação single-tenant: o salão é sempre o principal, definido no servidor.
        $this->merge(['salao_id' => Salao::principalId()]);
    }

    public function rules(): array
    {
        return [
            'salao_id'  => ['required', 'exists:saloes,id'],
            'nome'      => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'cor'       => ['nullable', 'string', 'max:7'],
            'icone'     => ['nullable', 'string', 'max:50'],
            'ordem'     => ['nullable', 'integer', 'min:0'],
            'ativo'     => ['sometimes', 'boolean'],
        ];
    }
}
