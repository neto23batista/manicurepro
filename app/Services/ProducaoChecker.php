<?php

namespace App\Services;

/**
 * Verifica a prontidão da aplicação para produção. Cada item retorna um nível
 * (ok | aviso | erro) e uma mensagem. Usado pelo comando manicure:verificar-producao.
 */
class ProducaoChecker
{
    public const OK = 'ok';

    public const AVISO = 'aviso';

    public const ERRO = 'erro';

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
            : $this->aviso('Ambiente', 'APP_ENV='.app()->environment().' (use "production" no servidor).');

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

        // Agendador (cron) — sempre aviso: não dá para detectar cron do SO daqui.
        $checks[] = $this->aviso(
            'Agendador',
            'Lembrete permanente: configure cron "* * * * * php artisan schedule:run" (lembretes, aniversários, limpeza, CRM, backup).',
        );

        // CSP
        $checks[] = config('manicure.security.csp_enabled')
            ? $this->ok('CSP', 'Content-Security-Policy habilitada.')
            : $this->aviso('CSP', 'CSP desabilitada (CSP_ENABLED=false).');

        // storage:link
        $checks[] = file_exists(public_path('storage'))
            ? $this->ok('Storage', 'Link público de storage existe.')
            : $this->erro('Storage', 'Falta o link de storage. Rode "php artisan storage:link" (galeria/logos não aparecem sem ele).');

        // Mercado Pago: se habilitado, exige token + secret de webhook (fail-closed)
        $mpEnabled = (bool) config('manicure.pagamento.mercadopago.enabled');
        $mpSecret = (string) config('manicure.pagamento.mercadopago.webhook_secret');
        $mpToken = (string) config('manicure.pagamento.mercadopago.access_token');
        if ($mpEnabled) {
            if ($mpToken === '') {
                $checks[] = $ehProducao
                    ? $this->erro('MercadoPago', 'MP_ENABLED=true sem MP_ACCESS_TOKEN. Cobranças Pix falharão.')
                    : $this->aviso('MercadoPago', 'MP habilitado sem MP_ACCESS_TOKEN (obrigatório em produção).');
            } elseif ($mpSecret === '') {
                $checks[] = $ehProducao
                    ? $this->erro('MercadoPago', 'MP_ENABLED=true sem MP_WEBHOOK_SECRET. Webhooks serão rejeitados.')
                    : $this->aviso('MercadoPago', 'MP habilitado sem webhook secret (obrigatório em produção).');
            } else {
                $checks[] = $this->ok('MercadoPago', 'MP habilitado com token e MP_WEBHOOK_SECRET.');
            }
        } else {
            $checks[] = $this->ok('MercadoPago', 'Pagamento online desabilitado (MP_ENABLED=false).');
        }

        // Backup recente (ZIP em storage/app/backups)
        $checks[] = $this->checarBackupRecente();

        // Stubs que não devem parecer “ligados” em produção
        if ((bool) config('manicure.fiscal.enabled')) {
            $checks[] = $ehProducao
                ? $this->aviso('NF-e', 'FISCAL_ENABLED=true — apenas rascunho local (não emite SEFAZ). Prefira false em produção.')
                : $this->aviso('NF-e', 'FISCAL_ENABLED=true — stub local (não emite SEFAZ).');
        } else {
            $checks[] = $this->ok('NF-e', 'FISCAL_ENABLED=false (stub desligado).');
        }

        return $checks;
    }

    /**
     * @return array{nivel:string,item:string,msg:string}
     */
    private function checarBackupRecente(): array
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            return $this->aviso('Backup', 'Pasta storage/app/backups ausente. Rode "php artisan manicure:backup" e agende no cron.');
        }

        $zips = glob($dir.DIRECTORY_SEPARATOR.'*.zip') ?: [];
        if ($zips === []) {
            return $this->aviso('Backup', 'Nenhum ZIP de backup encontrado. Rode "php artisan manicure:backup".');
        }

        $maisRecente = max(array_map('filemtime', $zips));
        $idadeDias = (int) floor((time() - $maisRecente) / 86400);

        if ($idadeDias > 7) {
            return $this->aviso(
                'Backup',
                "Último backup há {$idadeDias} dias. Agende manicure:backup diário e copie ZIPs para fora do servidor.",
            );
        }

        return $this->ok('Backup', 'Backup recente encontrado (≤7 dias). Confira cópia off-server.');
    }

    /**
     * Há algum erro crítico? Aceita a lista já calculada para não repetir
     * verificar() — fonte única da definição de "crítico" (usada pelo command).
     *
     * @param  array<int, array{nivel:string, item:string, msg:string}>|null  $checks
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
