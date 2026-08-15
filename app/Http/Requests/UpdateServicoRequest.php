<?php

namespace App\Http\Requests;

use App\Models\Salao;
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
        $this->merge(['salao_id' => Salao::principalId()]);
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
            'comissao_fixo'       => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'custo_estimado'      => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'imagem'              => ['nullable', 'image', 'max:2048'],
            'remover_imagem'      => ['sometimes', 'boolean'],
            'combo'               => ['sometimes', 'boolean'],
            'disponivel_online'   => ['sometimes', 'boolean'],
            'ativo'               => ['sometimes', 'boolean'],
            'variacoes'           => ['nullable', 'array'],
            'variacoes.*.id'      => ['nullable', 'integer', 'exists:servico_variacoes,id'],
            'variacoes.*.nome'    => ['required_with:variacoes', 'string', 'max:100'],
            'variacoes.*.preco'   => ['required_with:variacoes', 'numeric', 'min:0', 'max:9999.99'],
            'variacoes.*.duracao' => ['required_with:variacoes', 'integer', 'min:5', 'max:600'],
            'variacoes.*.ordem'   => ['nullable', 'integer', 'min:0'],
            'variacoes.*.ativo'   => ['sometimes', 'boolean'],
        ];
    }

    public function validatedNormalized(): array
    {
        $data = $this->validated();
        unset($data['variacoes'], $data['imagem'], $data['remover_imagem']);
        $data['combo'] = $this->boolean('combo');
        $data['disponivel_online'] = $this->boolean('disponivel_online');
        $data['ativo'] = $this->boolean('ativo', true);
        $data['comissao_percentual'] = $this->filled('comissao_percentual')
            ? $data['comissao_percentual']
            : null;
        $data['comissao_fixo'] = $this->filled('comissao_fixo')
            ? $data['comissao_fixo']
            : null;
        $data['custo_estimado'] = $this->filled('custo_estimado')
            ? $data['custo_estimado']
            : null;

        return $data;
    }
}
