<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\CalendarConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * OAuth + sync best-effort com Google Calendar e Microsoft Graph.
 * Sem client_id configurado, métodos retornam null/false.
 */
class CalendarOAuthService
{
    public function configurado(string $provider): bool
    {
        $cfg = config("manicure.calendar.{$provider}");

        return is_array($cfg)
            && (bool) ($cfg['enabled'] ?? false)
            && filled($cfg['client_id'] ?? null)
            && filled($cfg['client_secret'] ?? null);
    }

    public function authorizationUrl(string $provider, User $user): ?string
    {
        if (! in_array($provider, ['google', 'outlook'], true) || ! $this->configurado($provider)) {
            return null;
        }

        $state = Str::random(40);
        session(["calendar_oauth.{$provider}.state" => $state, "calendar_oauth.{$provider}.user_id" => $user->id]);

        if ($provider === 'google') {
            $query = http_build_query([
                'client_id'     => config('manicure.calendar.google.client_id'),
                'redirect_uri'  => $this->redirectUri('google'),
                'response_type' => 'code',
                'scope'         => 'https://www.googleapis.com/auth/calendar.events email',
                'access_type'   => 'offline',
                'prompt'        => 'consent',
                'state'         => $state,
            ]);

            return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
        }

        $query = http_build_query([
            'client_id'     => config('manicure.calendar.outlook.client_id'),
            'redirect_uri'  => $this->redirectUri('outlook'),
            'response_type' => 'code',
            'scope'         => 'offline_access Calendars.ReadWrite User.Read',
            'state'         => $state,
        ]);

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.$query;
    }

    /**
     * Troca o code por tokens e persiste CalendarConnection.
     */
    public function handleCallback(string $provider, string $code, ?string $state, User $user): ?CalendarConnection
    {
        if (! in_array($provider, ['google', 'outlook'], true) || ! $this->configurado($provider)) {
            return null;
        }

        $expected = session("calendar_oauth.{$provider}.state");
        if ($expected && $state !== $expected) {
            return null;
        }

        try {
            $tokens = $provider === 'google'
                ? $this->exchangeGoogle($code)
                : $this->exchangeOutlook($code);

            if ($tokens === null) {
                return null;
            }

            $email = $tokens['email'] ?? null;

            return CalendarConnection::updateOrCreate(
                ['user_id' => $user->id, 'provider' => $provider],
                [
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'expires_at'    => isset($tokens['expires_in'])
                        ? now()->addSeconds((int) $tokens['expires_in'])
                        : null,
                    'email' => $email,
                    'meta'  => ['conectado_em' => now()->toIso8601String()],
                ],
            );
        } catch (Throwable $e) {
            Log::warning('Calendar OAuth callback falhou.', [
                'provider' => $provider,
                'erro'     => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function disconnect(User $user, string $provider): bool
    {
        if (! in_array($provider, ['google', 'outlook'], true)) {
            return false;
        }

        $deleted = CalendarConnection::where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Cria ou atualiza evento remoto nas conexões do dono do agendamento (user_id).
     * Best-effort: retorna false se não houver conexão ou se a API falhar.
     */
    public function syncAgendamento(Agendamento $agendamento, ?User $user = null): bool
    {
        $user ??= $agendamento->user;
        if ($user === null) {
            return false;
        }

        $conexoes = CalendarConnection::where('user_id', $user->id)->get();
        if ($conexoes->isEmpty()) {
            return false;
        }

        $agendamento->loadMissing(['salao', 'manicure', 'servicos', 'cliente']);
        $ok = false;

        foreach ($conexoes as $conexao) {
            try {
                if ($conexao->isGoogle() && $this->configurado('google')) {
                    $ok = $this->syncGoogle($conexao, $agendamento) || $ok;
                } elseif ($conexao->isOutlook() && $this->configurado('outlook')) {
                    $ok = $this->syncOutlook($conexao, $agendamento) || $ok;
                }
            } catch (Throwable $e) {
                Log::warning('Sync calendário falhou.', [
                    'provider'       => $conexao->provider,
                    'agendamento_id' => $agendamento->id,
                    'erro'           => $e->getMessage(),
                ]);
            }
        }

        return $ok;
    }

    private function redirectUri(string $provider): string
    {
        $configured = config("manicure.calendar.{$provider}.redirect");

        return filled($configured)
            ? (string) $configured
            : route('calendar.oauth.callback', ['provider' => $provider]);
    }

    /** @return array{access_token: string, refresh_token?: string, expires_in?: int, email?: string}|null */
    private function exchangeGoogle(string $code): ?array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => config('manicure.calendar.google.client_id'),
            'client_secret' => config('manicure.calendar.google.client_secret'),
            'redirect_uri'  => $this->redirectUri('google'),
            'grant_type'    => 'authorization_code',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $email = null;

        if (! empty($data['access_token'])) {
            $info = Http::withToken($data['access_token'])
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');
            if ($info->successful()) {
                $email = $info->json('email');
            }
        }

        return [
            'access_token'  => (string) $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in'    => isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            'email'         => $email,
        ];
    }

    /** @return array{access_token: string, refresh_token?: string, expires_in?: int, email?: string}|null */
    private function exchangeOutlook(string $code): ?array
    {
        $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'code'          => $code,
            'client_id'     => config('manicure.calendar.outlook.client_id'),
            'client_secret' => config('manicure.calendar.outlook.client_secret'),
            'redirect_uri'  => $this->redirectUri('outlook'),
            'grant_type'    => 'authorization_code',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        $email = null;

        if (! empty($data['access_token'])) {
            $me = Http::withToken($data['access_token'])
                ->get('https://graph.microsoft.com/v1.0/me');
            if ($me->successful()) {
                $email = $me->json('mail') ?? $me->json('userPrincipalName');
            }
        }

        return [
            'access_token'  => (string) $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in'    => isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            'email'         => $email,
        ];
    }

    private function syncGoogle(CalendarConnection $conexao, Agendamento $agendamento): bool
    {
        $meta = $conexao->meta ?? [];
        $eventId = $meta['events'][(string) $agendamento->id] ?? null;
        $payload = $this->eventoPayloadGoogle($agendamento);

        if ($eventId) {
            $response = Http::withToken($conexao->access_token)
                ->put('https://www.googleapis.com/calendar/v3/calendars/primary/events/'.$eventId, $payload);
        } else {
            $response = Http::withToken($conexao->access_token)
                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $payload);
        }

        if (! $response->successful()) {
            return false;
        }

        $remoteId = $response->json('id') ?? $eventId;
        if ($remoteId) {
            $meta['events'][(string) $agendamento->id] = $remoteId;
            $conexao->update(['meta' => $meta]);
        }

        return true;
    }

    private function syncOutlook(CalendarConnection $conexao, Agendamento $agendamento): bool
    {
        $meta = $conexao->meta ?? [];
        $eventId = $meta['events'][(string) $agendamento->id] ?? null;
        $payload = $this->eventoPayloadOutlook($agendamento);

        if ($eventId) {
            $response = Http::withToken($conexao->access_token)
                ->patch('https://graph.microsoft.com/v1.0/me/events/'.$eventId, $payload);
        } else {
            $response = Http::withToken($conexao->access_token)
                ->post('https://graph.microsoft.com/v1.0/me/events', $payload);
        }

        if (! $response->successful()) {
            return false;
        }

        $remoteId = $response->json('id') ?? $eventId;
        if ($remoteId) {
            $meta['events'][(string) $agendamento->id] = $remoteId;
            $conexao->update(['meta' => $meta]);
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function eventoPayloadGoogle(Agendamento $agendamento): array
    {
        $titulo = $this->tituloEvento($agendamento);

        return [
            'summary'     => $titulo,
            'description' => $this->descricaoEvento($agendamento),
            'location'    => $agendamento->salao?->nome,
            'start'       => [
                'dateTime' => $agendamento->data_hora_inicio->toIso8601String(),
                'timeZone' => config('app.timezone', 'America/Sao_Paulo'),
            ],
            'end' => [
                'dateTime' => $agendamento->data_hora_fim->toIso8601String(),
                'timeZone' => config('app.timezone', 'America/Sao_Paulo'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function eventoPayloadOutlook(Agendamento $agendamento): array
    {
        return [
            'subject' => $this->tituloEvento($agendamento),
            'body'    => [
                'contentType' => 'Text',
                'content'     => $this->descricaoEvento($agendamento),
            ],
            'location' => [
                'displayName' => $agendamento->salao?->nome ?? '',
            ],
            'start' => [
                'dateTime' => $agendamento->data_hora_inicio->format('Y-m-d\TH:i:s'),
                'timeZone' => config('app.timezone', 'America/Sao_Paulo'),
            ],
            'end' => [
                'dateTime' => $agendamento->data_hora_fim->format('Y-m-d\TH:i:s'),
                'timeZone' => config('app.timezone', 'America/Sao_Paulo'),
            ],
        ];
    }

    private function tituloEvento(Agendamento $agendamento): string
    {
        $servicos = $agendamento->servicos->pluck('nome')->filter()->implode(', ');

        return $servicos !== ''
            ? 'Agendamento: '.$servicos
            : 'Agendamento #'.$agendamento->id;
    }

    private function descricaoEvento(Agendamento $agendamento): string
    {
        $linhas = [
            'Cliente: '.($agendamento->nome_cliente_exibido ?? '—'),
            'Profissional: '.($agendamento->manicure?->nome ?? '—'),
            'Salão: '.($agendamento->salao?->nome ?? '—'),
        ];

        return implode("\n", $linhas);
    }
}
