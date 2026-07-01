<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nome'      => ['required', 'string', 'max:255'],
            'email'     => ['nullable', 'email'],
            'telefone'  => ['nullable', 'string', 'max:20'],
            'whatsapp'  => ['nullable', 'string', 'max:20'],
            'endereco'  => ['nullable', 'string'],
            'numero'    => ['nullable', 'string', 'max:20'],
            'bairro'    => ['nullable', 'string'],
            'cidade'    => ['nullable', 'string'],
            'estado'    => ['nullable', 'string', 'max:2'],
            'cep'       => ['nullable', 'string', 'max:9'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'instagram' => ['nullable', 'string'],
            'descricao' => ['nullable', 'string'],
            'ativo'     => ['boolean'],
        ];
    }
}
