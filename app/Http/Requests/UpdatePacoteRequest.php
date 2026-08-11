<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePacoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->salao_id !== null;
    }

    public function rules(): array
    {
        return [
            'nome'          => ['required', 'string', 'max:120'],
            'sessoes'       => ['required', 'integer', 'min:1', 'max:100'],
            'validade_dias' => ['nullable', 'integer', 'min:1', 'max:730'],
            'preco'         => ['required', 'numeric', 'min:0'],
            'ativo'         => ['sometimes', 'boolean'],
        ];
    }
}
