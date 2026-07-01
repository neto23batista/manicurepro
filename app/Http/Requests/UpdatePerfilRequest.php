<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'phone'        => ['nullable', 'string', 'max:20'],
            'avatar'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password'     => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'O nome é obrigatório.',
            'email.required'            => 'O e-mail é obrigatório.',
            'email.email'               => 'E-mail inválido.',
            'email.unique'              => 'Este e-mail já está em uso.',
            'avatar.image'              => 'A foto deve ser uma imagem.',
            'avatar.max'                => 'Foto não pode passar de 2 MB.',
            'current_password.required_with' => 'Informe a senha atual para trocar de senha.',
            'current_password.current_password' => 'Senha atual incorreta.',
            'password.min'              => 'A nova senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'        => 'A confirmação da nova senha não confere.',
        ];
    }
}
