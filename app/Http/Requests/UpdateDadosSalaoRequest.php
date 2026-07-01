<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDadosSalaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->salao_id !== null;
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
            'estado'    => ['nullable', 'string', 'size:2'],
            'cep'       => ['nullable', 'string', 'max:9'],
            'instagram' => ['nullable', 'string'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'logo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'foto_capa' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.image'      => 'A logo deve ser uma imagem.',
            'logo.max'        => 'A logo não pode passar de 3 MB.',
            'foto_capa.image' => 'A capa deve ser uma imagem.',
            'foto_capa.max'   => 'A capa não pode passar de 5 MB.',
        ];
    }
}
