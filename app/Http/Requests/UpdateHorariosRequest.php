<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHorariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->salao_id !== null;
    }

    public function rules(): array
    {
        return [
            'horarios'                   => ['required', 'array'],
            'horarios.*.ativo'           => ['sometimes', 'boolean'],
            'horarios.*.hora_abertura'   => ['nullable', 'date_format:H:i'],
            'horarios.*.hora_fechamento' => ['nullable', 'date_format:H:i', 'after:horarios.*.hora_abertura'],
        ];
    }
}
