<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGaleriaFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['dono', 'atendente', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'fotos'       => ['required', 'array', 'min:1', 'max:20'],
            'fotos.*'     => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'titulo'      => ['nullable', 'string', 'max:120'],
            'manicure_id' => ['nullable', 'integer', 'exists:manicures,id'],
            'publicar'    => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'fotos.required' => 'Selecione ao menos uma foto.',
            'fotos.*.image'  => 'Cada arquivo deve ser uma imagem (JPG, PNG ou WEBP).',
            'fotos.*.max'    => 'Cada imagem pode ter no máximo 5 MB.',
        ];
    }
}
