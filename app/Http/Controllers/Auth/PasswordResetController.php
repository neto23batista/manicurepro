<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /** Tela "esqueci minha senha" */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.forgot');
    }

    /** Envia link de redefinição por e-mail */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email'], [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email'    => 'E-mail inválido.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', 'Link de redefinição enviado! Verifique seu e-mail.')
            : back()->withErrors(['email' => __($status)]);
    }

    /** Tela de redefinição com o token */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /** Aplica nova senha */
    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min'       => 'Senha mínima de 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Senha redefinida com sucesso! Faça login.')
            : back()->withErrors(['email' => [__($status)]]);
    }
}
