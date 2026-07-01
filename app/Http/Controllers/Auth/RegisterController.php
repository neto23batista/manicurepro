<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Cliente;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request)
    {
        // Instalação single-tenant: o cadastro é sempre para o salão único.
        return view('auth.register', ['salao' => Salao::principal()]);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        // O salão é definido pelo servidor — o cliente não escolhe.
        $salaoId = Salao::principalId();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => UserRole::Cliente->value,
            'phone'    => $data['phone'] ?? null,
            'salao_id' => $salaoId,
        ]);

        if ($salaoId) {
            Cliente::create([
                'user_id'  => $user->id,
                'salao_id' => $salaoId,
                'nome'     => $user->name,
                'email'    => $user->email,
                'telefone' => $user->phone,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('cliente.dashboard')
            ->with('success', 'Conta criada com sucesso! Verifique seu e-mail para confirmar sua conta. 💅');
    }
}
