<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        // Proteção contra força-bruta / credential stuffing (por e-mail + IP)
        $throttleKey = Str::lower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $segundos = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Muitas tentativas de login. Tente novamente em {$segundos} segundos.",
            ])->onlyInput('email');
        }

        if (!Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->ativo) {
            Auth::logout();
            return back()->withErrors(['email' => 'Sua conta está inativa. Entre em contato com o suporte.']);
        }

        // Verificação em duas etapas: interrompe o login até validar o código TOTP
        if ($user->hasTwoFactorEnabled()) {
            $remember = $request->boolean('remember');
            Auth::logout();
            $request->session()->put('2fa:user', $user->id);
            $request->session()->put('2fa:remember', $remember);
            return redirect()->route('2fa.challenge');
        }

        $request->session()->regenerate();

        return redirect($this->redirectAfterLogin($user));
    }

    private function redirectAfterLogin(User $user): string
    {
        $role = UserRole::tryFrom($user->role);

        return $role
            ? route($role->dashboardRoute())
            : '/';
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
