<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCupomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cupom = $this->route('cupom');

        return $cupom
            ? ($this->user()?->can('update', $cupom) ?? false)
            : false;
    }

    public function rules(): array
    {
        $salaoId = $this->user()?->salao_id;

        return [
            'codigo'                   => ['required', 'string', 'max:30'],
            'tipo'                     => ['required', 'in:percentual,fixo'],
            'valor'                    => ['required', 'numeric', 'min:0'],
            'minimo_pedido'            => ['nullable', 'numeric', 'min:0'],
            'maximo_desconto'          => ['nullable', 'numeric', 'min:0'],
            'uso_maximo'               => ['nullable', 'integer', 'min:1'],
            'uso_maximo_por_cliente'   => ['nullable', 'integer', 'min:1'],
            'validade'                 => ['nullable', 'date'],
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
}
