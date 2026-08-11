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
            'fotos'   => ['required', 'array', 'min:1', 'max:20'],
            'fotos.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120',
                'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000',
            ],
            'titulo'      => ['nullable', 'string', 'max:120'],
            'manicure_id' => ['nullable', 'integer', 'exists:manicures,id'],
            'publicar'    => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'fotos.required'     => 'Selecione ao menos uma foto.',
            'fotos.*.image'      => 'Cada arquivo deve ser uma imagem (JPG, PNG ou WEBP).',
            'fotos.*.mimes'      => 'Cada arquivo deve ser JPG, PNG ou WEBP.',
            'fotos.*.mimetypes'  => 'Tipo MIME inválido. Use JPG, PNG ou WEBP.',
            'fotos.*.max'        => 'Cada imagem pode ter no máximo 5 MB.',
            'fotos.*.dimensions' => 'Cada imagem deve ter entre 200×200 e 8000×8000 pixels.',
        ];
    }

    public function attributes(): array
    {
        return [
            'fotos'   => 'fotos',
            'fotos.*' => 'foto',
        ];
    }
}
