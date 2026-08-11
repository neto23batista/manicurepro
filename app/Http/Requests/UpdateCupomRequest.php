<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'codigo'          => ['required', 'string', 'max:30'],
            'tipo'            => ['required', 'in:percentual,fixo'],
            'valor'           => ['required', 'numeric', 'min:0'],
            'minimo_pedido'   => ['nullable', 'numeric', 'min:0'],
            'maximo_desconto' => ['nullable', 'numeric', 'min:0'],
            'uso_maximo'      => ['nullable', 'integer', 'min:1'],
            'validade'        => ['nullable', 'date'],
            'ativo'           => ['sometimes', 'boolean'],
        ];
    }
}
