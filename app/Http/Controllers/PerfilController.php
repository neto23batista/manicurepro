<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePerfilRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('perfil.edit', ['user' => auth()->user()]);
    }

    public function update(UpdatePerfilRequest $request)
    {
        $user = $request->user();

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // O UserObserver replica nome/email/telefone/avatar para Manicure/Cliente.
        $user->update($data);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public function destroyAvatar()
    {
        $user = auth()->user();
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->update(['avatar' => null]);
        return back()->with('success', 'Foto removida.');
    }

    /**
     * LGPD — portabilidade: baixa todos os dados do usuário em JSON.
     */
    public function exportarDados()
    {
        $user = auth()->user()->loadMissing('cliente');
        $cliente = $user->cliente;

        $dados = [
            'gerado_em' => now()->toIso8601String(),
            'usuario' => $user->only(['id', 'name', 'email', 'phone', 'role', 'created_at']),
            'cliente' => $cliente?->only(['id', 'nome', 'email', 'telefone', 'cpf', 'data_nascimento', 'pontos_fidelidade', 'total_visitas', 'total_gasto']),
            'agendamentos' => $cliente
                ? $cliente->agendamentos()->with('servicos:id,nome')->get()->map(fn($a) => [
                    'data' => $a->data_hora_inicio->toDateTimeString(),
                    'status' => $a->status,
                    'servicos' => $a->servicos->pluck('nome'),
                    'valor_total' => $a->valor_total,
                ])
                : [],
            'avaliacoes' => $cliente ? $cliente->avaliacoes()->get(['nota', 'comentario', 'created_at']) : [],
            'fidelidade' => $cliente ? $cliente->pontosFidelidade()->get(['pontos', 'tipo', 'descricao', 'created_at']) : [],
        ];

        $nome = 'meus-dados-' . now()->format('Y-m-d') . '.json';

        return response()->json($dados, 200, [
            'Content-Disposition' => 'attachment; filename="' . $nome . '"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * LGPD — direito ao esquecimento: exclui a conta após confirmar a senha.
     */
    public function excluirConta(Request $request)
    {
        $request->validate(['password' => 'required|current_password']);

        $user = auth()->user();

        // Donos não podem se autoexcluir (precisam transferir o salão antes)
        if ($user->isDono() || $user->isSuperAdmin()) {
            return back()->withErrors(['password' => 'Contas de dono/admin não podem ser excluídas por aqui. Contate o suporte.']);
        }

        $cliente = $user->cliente;

        Auth::logout();

        if ($cliente) {
            $cliente->delete(); // soft delete (preserva histórico do salão)
        }
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.index')
            ->with('success', 'Sua conta foi excluída. Sentiremos sua falta! 🌸');
    }
}
