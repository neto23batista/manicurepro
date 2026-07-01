# Guia de produção — Fernanda Silva Nails

Passo a passo para colocar o sistema no ar com segurança. Rode
`php artisan manicure:verificar-producao` a qualquer momento para um checklist
automático do ambiente.

---

## 1. Pré-requisitos do servidor

- PHP 8.3+ com extensões: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `fileinfo`, `zip`.
- MySQL 8 (ou MariaDB 10.6+).
- Node 18+ (apenas para gerar os assets no deploy).
- Um domínio com **HTTPS** (certificado válido — Let's Encrypt serve).

## 2. Variáveis de ambiente (`.env`)

Use o `.env.production.example` como base. Os pontos **críticos**:

```dotenv
APP_ENV=production
APP_DEBUG=false                 # NUNCA true em produção
APP_URL=https://seudominio.com  # precisa ser https
APP_KEY=                        # gere com: php artisan key:generate

# Banco
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# E-mail real (sem isto, confirmações/lembretes/aniversários NÃO são enviados)
MAIL_MAILER=smtp
MAIL_HOST=smtp.seuprovedor.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="contato@seudominio.com"

QUEUE_CONNECTION=database       # use um worker (passo 5)
SESSION_SECURE_COOKIE=true      # cookies só por https
```

> Em `APP_ENV=production` o sistema **força HTTPS** e cookies seguros
> automaticamente (ver `AppServiceProvider`). Ainda assim mantenha `APP_URL` com `https://`.

## 3. Deploy da aplicação

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # gera public/build (CSS/JS)
php artisan migrate --force
php artisan storage:link         # necessário para galeria, logos e capas
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ao atualizar versões, rode novamente os `*:cache` (ou `php artisan optimize`).
Se mudar `.env`, rode `php artisan config:clear` antes de recachear.

## 4. Permissões

`storage/` e `bootstrap/cache/` precisam ser graváveis pelo usuário do PHP-FPM
(geralmente `www-data`).

## 5. Worker de fila (e-mails, WhatsApp, notificações)

As notificações implementam `ShouldQueue` — sem um worker ativo elas **não saem**.
Mantenha um worker rodando com Supervisor:

```ini
; /etc/supervisor/conf.d/manicure-worker.conf
[program:manicure-worker]
command=php /var/www/manicurepro/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/manicurepro/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl start manicure-worker
```

Após cada deploy: `php artisan queue:restart` (faz os workers recarregarem o código).

## 6. Agendador (cron)

Os disparos automáticos (lembretes 24h/2h, **felicitações de aniversário**,
limpeza de reservas expiradas) dependem do scheduler. Adicione **uma** linha de cron:

```cron
* * * * * cd /var/www/manicurepro && php artisan schedule:run >> /dev/null 2>&1
```

Tarefas agendadas (ver `routes/console.php`):

| Comando                              | Quando            |
|--------------------------------------|-------------------|
| `manicure:enviar-lembretes 24h`      | diário 08:00      |
| `manicure:enviar-lembretes 2h`       | de hora em hora   |
| `manicure:enviar-aniversarios`       | diário 09:00      |
| `manicure:limpar-expirados`          | diário 03:00      |

## 7. Integrações opcionais (config-gated)

Desligadas por padrão; ative preenchendo o `.env`:

- **Mercado Pago (sinal via Pix):** `MP_ENABLED=true`, `MP_ACCESS_TOKEN`,
  `MP_WEBHOOK_SECRET`, `SINAL_HABILITADO=true`. Webhook: `POST /webhooks/mercadopago`.
- **WhatsApp (Cloud API):** `WHATSAPP_ENABLED=true`, `WHATSAPP_TOKEN`,
  `WHATSAPP_PHONE_NUMBER_ID` e os templates aprovados.
- **Aniversário:** ligado por padrão (`ANIVERSARIO_ENABLED=true`); gera cupom-presente
  (`ANIVERSARIO_CUPOM=true`, `ANIVERSARIO_CUPOM_VALOR=15`).

> **Pagamento online do valor cheio:** ainda não implementado. É uma extensão
> direta do fluxo de sinal — parametrizar `MercadoPagoService::criarPixSinal`
> para cobrar o total (em vez do percentual) e reaproveitar o mesmo webhook.

## 8. Pós-deploy — verificação

```bash
php artisan manicure:verificar-producao
```

Resolva todos os ✗ (críticos) e revise os ⚠ (avisos) antes de divulgar o site.

## 9. Backup

Agende backup diário do **banco** (`mysqldump`) e da pasta
`storage/app/public` (imagens de galeria, logos e capas).
