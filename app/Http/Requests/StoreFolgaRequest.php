<?php

namespace App\Http\Requests;

use App\Models\Folga;
use Illuminate\Foundation\Http\FormRequest;

class StoreFolgaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Folga::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'data'        => ['required', 'date'],
            'motivo'      => ['nullable', 'string', 'max:255'],
            'dia_todo'    => ['sometimes', 'boolean'],
            'hora_inicio' => ['nullable', 'required_if:dia_todo,0', 'date_format:H:i'],
            'hora_fim'    => ['nullable', 'required_if:dia_todo,0', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => 'Informe a data.',
        ];
    }
}
