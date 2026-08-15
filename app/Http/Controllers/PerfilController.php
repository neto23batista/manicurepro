<?php

namespace App\Http\Controllers;

use App\Enums\AgendamentoStatus;
use App\Http\Requests\UpdatePerfilRequest;
use App\Models\ListaEspera;
use App\Services\CalendarOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PerfilController extends Controller
{
    public function __construct(private CalendarOAuthService $calendario) {}

    public function edit()
    {
        $user = auth()->user()->load('calendarConnections');

        return view('perfil.edit', [
            'user'                => $user,
            'calendarConnections' => $user->calendarConnections->keyBy('provider'),
            'calendarGoogleOk'    => $this->calendario->configurado('google'),
            'calendarOutlookOk'   => $this->calendario->configurado('outlook'),
        ]);
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

        if ($request->filled('password')) {
            $user->tokens()->delete();
        }

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

        $listaEsperaQuery = ListaEspera::query()->where(function ($q) use ($user, $cliente) {
            $q->where('user_id', $user->id);
            if ($cliente) {
                $q->orWhere('cliente_id', $cliente->id);
            }
        });

        $dados = [
            'gerado_em' => now()->toIso8601String(),
            'usuario'   => $user->only(['id', 'name', 'email', 'phone', 'role', 'created_at']),
            'cliente'   => $cliente?->only([
                'id', 'nome', 'email', 'telefone', 'cpf', 'data_nascimento',
                'pontos_fidelidade', 'total_visitas', 'total_gasto',
            ]),
            'agendamentos' => $cliente
                ? $cliente->agendamentos()->with('servicos:id,nome')->get()->map(fn ($a) => [
                    'data'        => $a->data_hora_inicio->toDateTimeString(),
                    'status'      => $a->status,
                    'servicos'    => $a->servicos->pluck('nome'),
                    'valor_total' => $a->valor_total,
                ])
                : [],
            'avaliacoes' => $cliente
                ? $cliente->avaliacoes()->get(['nota', 'comentario', 'created_at'])
                : [],
            'fidelidade' => $cliente
                ? $cliente->pontosFidelidade()->get(['pontos', 'tipo', 'descricao', 'created_at'])
                : [],
            'lista_espera' => $listaEsperaQuery->get([
                'data_preferida', 'periodo', 'status', 'notificado_em', 'created_at',
            ]),
        ];

        $nome = 'meus-dados-'.now()->format('Y-m-d').'.json';

        return response()->json($dados, 200, [
            'Content-Disposition' => 'attachment; filename="'.$nome.'"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * LGPD — direito ao esquecimento: anonimiza dados relacionados e exclui a conta.
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
        $manicure = $user->manicure;

        Auth::logout();

        DB::transaction(function () use ($user, $cliente, $manicure) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->notificacoes()->delete();
            $user->tokens()->delete();

            ListaEspera::query()
                ->where(function ($q) use ($user, $cliente) {
                    $q->where('user_id', $user->id);
                    if ($cliente) {
                        $q->orWhere('cliente_id', $cliente->id);
                    }
                })
                ->update([
                    'status'     => 'cancelado',
                    'user_id'    => null,
                    'cliente_id' => null,
                ]);

            if ($cliente) {
                $cliente->agendamentos()
                    ->whereIn('status', [
                        AgendamentoStatus::Aguardando->value,
                        AgendamentoStatus::Confirmado->value,
                    ])
                    ->where('data_hora_inicio', '>', now())
                    ->update(['status' => AgendamentoStatus::Cancelado->value]);

                $cliente->agendamentos()->update([
                    'nome_cliente'     => 'Cliente removido',
                    'telefone_cliente' => null,
                    'observacoes'      => null,
                    'user_id'          => null,
                ]);

                $cliente->avaliacoes()->update([
                    'comentario' => null,
                    'publicar'   => false,
                ]);

                $cliente->update([
                    'user_id'                => null,
                    'nome'                   => 'Cliente removido',
                    'email'                  => null,
                    'telefone'               => null,
                    'cpf'                    => null,
                    'data_nascimento'        => null,
                    'aniversario_enviado_em' => null,
                    'endereco'               => null,
                    'observacoes'            => null,
                    'alergias'               => null,
                    'pontos_fidelidade'      => 0,
                    'ativo'                  => false,
                ]);

                $cliente->delete(); // soft delete — preserva histórico operacional do salão
            }

            if ($manicure) {
                if ($manicure->foto && Storage::disk('public')->exists($manicure->foto)) {
                    Storage::disk('public')->delete($manicure->foto);
                }

                $manicure->update([
                    'user_id'  => null,
                    'nome'     => 'Profissional removido',
                    'email'    => null,
                    'telefone' => null,
                    'foto'     => null,
                    'bio'      => null,
                    'ativo'    => false,
                ]);
                $manicure->delete();
            }

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.index')
            ->with('success', 'Sua conta foi excluída. Sentiremos sua falta! 🌸');
    }
}
