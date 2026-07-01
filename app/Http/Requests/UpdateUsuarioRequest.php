<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', Rule::in(['admin', 'dono', 'atendente', 'manicure', 'cliente'])],
            'salao_id' => ['nullable', 'exists:saloes,id'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'ativo'    => ['sometimes', 'boolean'],
        ];
    }
}
