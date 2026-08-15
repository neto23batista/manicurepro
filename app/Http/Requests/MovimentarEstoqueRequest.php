<?php

namespace App\Http\Requests;

use App\Services\EstoqueService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimentarEstoqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $produto = $this->route('produto');

        return $produto
            ? ($this->user()?->can('update', $produto) ?? false)
            : false;
    }

    public function rules(): array
    {
        return [
            'tipo'       => ['required', Rule::in(EstoqueService::TIPOS)],
            'quantidade' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'motivo'     => [
                Rule::requiredIf(fn () => in_array(
                    $this->input('tipo'),
                    EstoqueService::TIPOS_MOTIVO_OBRIGATORIO,
                    true,
                )),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Informe o motivo para perda, consumo interno ou devolução.',
        ];
    }
}
