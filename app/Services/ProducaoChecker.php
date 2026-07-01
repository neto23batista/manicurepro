<?php

namespace App\Services;

/**
 * Verifica a prontidão da aplicação para produção. Cada item retorna um nível
 * (ok | aviso | erro) e uma mensagem. Usado pelo comando manicure:verificar-producao.
 */
class ProducaoChecker
{
    public const OK    = 'ok';
    public const AVISO = 'aviso';
    public const ERRO  = 'erro';

    /**
     * @return array<int, array{nivel:string, item:string, msg:string}>
     */
    public function verificar(): array
    {
        $ehProducao = app()->environment('production');
        $checks = [];

        // Ambiente
        $checks[] = $ehProducao
            ? $this->ok('Ambiente', 'APP_ENV=production')
            : $this->aviso('Ambiente', 'APP_ENV=' . app()->environment() . ' (use "production" no servidor).');

        // Debug
        $debug = (bool) config('app.debug');
        $checks[] = ($debug && $ehProducao)
            ? $this->erro('Debug', 'APP_DEBUG=true em produção expõe dados sensíveis. Defina APP_DEBUG=false.')
            : ($debug
                ? $this->aviso('Debug', 'APP_DEBUG=true (aceitável fora de produção).')
                : $this->ok('Debug', 'APP_DEBUG=false'));

        // HTTPS
        $url = (string) config('app.url');
        $checks[] = str_starts_with($url, 'https://')
            ? $this->ok('HTTPS', 'APP_URL usa https.')
            : ($ehProducao
                ? $this->erro('HTTPS', "APP_URL=\"{$url}\" não usa https. Em produção use https://.")
                : $this->aviso('HTTPS', "APP_URL=\"{$url}\" sem https (ok em local)."));

        // APP_KEY
        $checks[] = config('app.key')
            ? $this->ok('APP_KEY', 'Chave da aplicação definida.')
            : $this->erro('APP_KEY', 'APP_KEY ausente. Rode "php artisan key:generate".');

        // E-mail
        $mailer = (string) config('mail.default');
        $checks[] = in_array($mailer, ['log', 'array'], true)
            ? $this->aviso('E-mail', "MAIL_MAILER={$mailer}: os e-mails NÃO são enviados de fato. Configure SMTP em produção.")
            : $this->ok('E-mail', "MAIL_MAILER={$mailer}");

        // Fila
        $queue = (string) config('queue.default');
        $checks[] = $queue === 'sync'
            ? $this->aviso('Fila', 'QUEUE_CONNECTION=sync: jobs rodam no mesmo request (e-mails/WhatsApp deixam a navegação lenta).')
            : $this->ok('Fila', "QUEUE_CONNECTION={$queue} — mantenha um worker ativo (php artisan queue:work).");

        // Agendador (cron)
        $checks[] = $this->aviso('Agendador', 'Configure o cron para "php artisan schedule:run" a cada minuto (lembretes, aniversários, limpeza).');

        // CSP
        $checks[] = config('manicure.security.csp_enabled')
            ? $this->ok('CSP', 'Content-Security-Policy habilitada.')
            : $this->aviso('CSP', 'CSP desabilitada (CSP_ENABLED=false).');

        // storage:link
        $checks[] = file_exists(public_path('storage'))
            ? $this->ok('Storage', 'Link público de storage existe.')
            : $this->erro('Storage', 'Falta o link de storage. Rode "php artisan storage:link" (galeria/logos não aparecem sem ele).');

        return $checks;
    }

    /**
     * Há algum erro crítico? Aceita a lista já calculada para não repetir
     * verificar() — fonte única da definição de "crítico" (usada pelo command).
     *
     * @param array<int, array{nivel:string, item:string, msg:string}>|null $checks
     */
    public function temErroCritico(?array $checks = null): bool
    {
        foreach ($checks ?? $this->verificar() as $c) {
            if ($c['nivel'] === self::ERRO) {
                return true;
            }
        }
        return false;
    }

    private function ok(string $item, string $msg): array
    {
        return ['nivel' => self::OK, 'item' => $item, 'msg' => $msg];
    }

    private function aviso(string $item, string $msg): array
    {
        return ['nivel' => self::AVISO, 'item' => $item, 'msg' => $msg];
    }

    private function erro(string $item, string $msg): array
    {
        return ['nivel' => self::ERRO, 'item' => $item, 'msg' => $msg];
    }
}
