<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Instalação single-tenant: o salão é sempre o principal, definido no servidor.
        $this->merge(['salao_id' => \App\Models\Salao::principalId()]);
    }

    public function rules(): array
    {
        return [
            'salao_id'            => ['required', 'exists:saloes,id'],
            'categoria_id'        => ['nullable', 'exists:categorias_servico,id'],
            'nome'                => ['required', 'string', 'max:255'],
            'descricao'           => ['nullable', 'string', 'max:1000'],
            'preco'               => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'duracao'             => ['required', 'integer', 'min:5', 'max:600'],
            'comissao_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'combo'               => ['sometimes', 'boolean'],
            'disponivel_online'   => ['sometimes', 'boolean'],
            'ativo'               => ['sometimes', 'boolean'],
        ];
    }

    public function validatedNormalized(): array
    {
        $data = $this->validated();
        $data['combo']             = $this->boolean('combo');
        $data['disponivel_online'] = $this->boolean('disponivel_online');
        $data['ativo']             = $this->boolean('ativo', true);
        return $data;
    }
}
