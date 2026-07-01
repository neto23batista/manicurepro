<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function __construct(private TotpService $totp) {}

    public function setup(Request $request)
    {
        $user = auth()->user();

        if ($user->hasTwoFactorEnabled()) {
            return view('perfil.two-factor', ['ativo' => true, 'secret' => null, 'uri' => null]);
        }

        $secret = $request->session()->get('2fa:setup_secret');
        if (!$secret) {
            $secret = $this->totp->generateSecret();
            $request->session()->put('2fa:setup_secret', $secret);
        }

        $uri = $this->totp->otpauthUri($secret, $user->email, config('app.name', 'Fernanda Silva Nails'));

        return view('perfil.two-factor', ['ativo' => false, 'secret' => $secret, 'uri' => $uri]);
    }

    public function enable(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = auth()->user();
        $secret = $request->session()->get('2fa:setup_secret');

        if (!$secret || !$this->totp->verify($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido. Confira o relógio do app e tente de novo.']);
        }

        $user->update(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()]);
        $request->session()->forget('2fa:setup_secret');

        return redirect()->route('2fa.setup')->with('success', 'Verificação em duas etapas ativada! 🔒');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        auth()->user()->update(['two_factor_secret' => null, 'two_factor_confirmed_at' => null]);

        return redirect()->route('2fa.setup')->with('success', 'Verificação em duas etapas desativada.');
    }

    public function challenge(Request $request)
    {
        if (!$request->session()->has('2fa:user')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function challengeVerify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $userId = $request->session()->get('2fa:user');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user || !$user->hasTwoFactorEnabled() || !$this->totp->verify($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        $remember = (bool) $request->session()->pull('2fa:remember', false);
        $request->session()->forget('2fa:user');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $role = UserRole::tryFrom($user->role);

        return redirect($role ? route($role->dashboardRoute()) : '/');
    }
}
