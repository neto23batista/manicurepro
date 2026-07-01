<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCupomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->salao_id !== null;
    }

    public function rules(): array
    {
        $salaoId = $this->user()->salao_id;

        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('cupons', 'codigo')->where(fn($q) => $q->where('salao_id', $salaoId)),
            ],
            'tipo'            => ['required', 'in:percentual,fixo'],
            'valor'           => ['required', 'numeric', 'min:0'],
            'minimo_pedido'   => ['nullable', 'numeric', 'min:0'],
            'maximo_desconto' => ['nullable', 'numeric', 'min:0'],
            'uso_maximo'      => ['nullable', 'integer', 'min:1'],
            'validade'        => ['nullable', 'date', 'after:today'],
            'ativo'           => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Informe um código.',
            'codigo.unique'   => 'Este código já existe.',
            'valor.required'  => 'Informe o valor do desconto.',
            'validade.after'  => 'A validade deve ser futura.',
        ];
    }
}
