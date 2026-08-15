<?php

namespace App\Http\Controllers;

use App\Services\CalendarOAuthService;
use Illuminate\Http\Request;

class CalendarOAuthController extends Controller
{
    public function __construct(private CalendarOAuthService $calendario) {}

    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, ['google', 'outlook'], true), 404);

        $url = $this->calendario->authorizationUrl($provider, auth()->user());
        if ($url === null) {
            return redirect()
                ->route('perfil.edit')
                ->with('error', 'Integração de calendário não configurada.');
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, ['google', 'outlook'], true), 404);

        if ($request->filled('error')) {
            return redirect()
                ->route('perfil.edit')
                ->with('error', 'Não foi possível conectar o calendário.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()
                ->route('perfil.edit')
                ->with('error', 'Código de autorização ausente.');
        }

        $conexao = $this->calendario->handleCallback(
            $provider,
            $code,
            $request->query('state'),
            auth()->user(),
        );

        if ($conexao === null) {
            return redirect()
                ->route('perfil.edit')
                ->with('error', 'Falha ao conectar o calendário. Tente novamente.');
        }

        $nome = $provider === 'google' ? 'Google' : 'Outlook';

        return redirect()
            ->route('perfil.edit')
            ->with('success', "Calendário {$nome} conectado com sucesso.");
    }

    public function disconnect(string $provider)
    {
        abort_unless(in_array($provider, ['google', 'outlook'], true), 404);

        $ok = $this->calendario->disconnect(auth()->user(), $provider);
        $nome = $provider === 'google' ? 'Google' : 'Outlook';

        return redirect()
            ->route('perfil.edit')
            ->with($ok ? 'success' : 'error', $ok
                ? "Calendário {$nome} desconectado."
                : "Nenhuma conexão {$nome} encontrada.");
    }
}
