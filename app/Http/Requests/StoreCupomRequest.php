<?php

namespace App\Http\Requests;

use App\Models\Cupom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCupomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cupom::class) ?? false;
    }

    public function rules(): array
    {
        $salaoId = $this->user()?->salao_id;

        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('cupons', 'codigo')->where(fn ($q) => $q->where('salao_id', $salaoId)),
            ],
            'tipo'                     => ['required', 'in:percentual,fixo'],
            'valor'                    => ['required', 'numeric', 'min:0'],
            'minimo_pedido'            => ['nullable', 'numeric', 'min:0'],
            'maximo_desconto'          => ['nullable', 'numeric', 'min:0'],
            'uso_maximo'               => ['nullable', 'integer', 'min:1'],
            'uso_maximo_por_cliente'    => ['nullable', 'integer', 'min:1'],
            'validade'                 => ['nullable', 'date', 'after:today'],
            'ativo'                    => ['sometimes', 'boolean'],
            'primeira_compra'          => ['sometimes', 'boolean'],
            'anti_stacking_fidelidade' => ['sometimes', 'boolean'],
            'cliente_id'               => [
                'nullable', 'integer',
                Rule::exists('clientes', 'id')->where(fn ($q) => $q->where('salao_id', $salaoId)),
            ],
            'servico_id' => [
                'nullable', 'integer',
                Rule::exists('servicos', 'id')->where(fn ($q) => $q->where('salao_id', $salaoId)),
            ],
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
