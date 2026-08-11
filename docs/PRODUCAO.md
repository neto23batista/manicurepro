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
limpeza de no-shows e holds) dependem do scheduler. Adicione **uma** linha de cron:

```cron
* * * * * cd /var/www/manicurepro && php artisan schedule:run >> /dev/null 2>&1
```

Horários seguem `APP_TIMEZONE` (padrão `America/Sao_Paulo`). As notificações
implementam `ShouldQueue` — o **worker de fila** (passo 5) precisa estar ativo
para e-mail/WhatsApp saírem de fato.

Tarefas agendadas (ver `routes/console.php`; todas com `withoutOverlapping`):

| Comando                              | Quando            | O que faz |
|--------------------------------------|-------------------|-----------|
| `manicure:enviar-lembretes 24h`      | diário 08:00      | Lembrete dos agendamentos de **amanhã** (`aguardando`/`confirmado`). Marca `lembrete_24h_em`. |
| `manicure:enviar-lembretes 2h`       | de hora em hora   | Lembrete dos que começam nas próximas ~2h15. Marca `lembrete_2h_em`. |
| `manicure:enviar-aniversarios`       | diário 09:00      | Felicita clientes ativos com aniversário hoje (29/02 → 28/02 em ano não bissexto). Marca `aniversario_enviado_em` e opcionalmente gera cupom `NIVER-{id}-{ano}`. |
| `manicure:limpar-expirados`          | diário 03:00      | Marca `nao_compareceu` se `data_hora_fim` passou há >2h (ainda aberto) e apaga `slot_holds` expirados. |

**Idempotência:** reexecutar o mesmo comando no mesmo dia não reenvia —
os campos `lembrete_*_em` / `aniversario_enviado_em` e o filtro de status
impedem duplicata. Em falha no `notify`, o marcador é liberado para retry.

Rodar manualmente (útil para smoke-test após deploy):

```bash
php artisan manicure:enviar-lembretes 24h
php artisan manicure:enviar-lembretes 2h
php artisan manicure:enviar-aniversarios
php artisan manicure:limpar-expirados
php artisan schedule:list   # confere o que o cron vai disparar
```

## 7. Integrações opcionais (config-gated)

Desligadas por padrão; ative preenchendo o `.env`:

- **Mercado Pago (Pix online):** `MP_ENABLED=true`, `MP_ACCESS_TOKEN`,
  `MP_WEBHOOK_SECRET`. Webhook: `POST /webhooks/mercadopago`.
  - Sinal antecipado: `SINAL_HABILITADO=true`, `SINAL_TIPO=percentual|fixo`, `SINAL_VALOR`.
  - Valor total/restante: `PAGAMENTO_TOTAL_HABILITADO=true` (cobra o líquido, ou o
    restante se o sinal já foi pago). Cancelamento do agendamento tenta cancelar/
    estornar a cobrança na MP (best-effort).
- **WhatsApp (Cloud API):** `WHATSAPP_ENABLED=true`, `WHATSAPP_TOKEN`,
  `WHATSAPP_PHONE_NUMBER_ID` e os templates aprovados.
- **Aniversário:** ligado por padrão (`ANIVERSARIO_ENABLED=true`); gera cupom-presente
  (`ANIVERSARIO_CUPOM=true`, `ANIVERSARIO_CUPOM_VALOR=15`).

## 8. Pós-deploy — verificação

```bash
php artisan manicure:verificar-producao
```

Resolva todos os ✗ (críticos) e revise os ⚠ (avisos) antes de divulgar o site.

## 9. Backup

Agende backup diário do **banco** (`mysqldump`) e da pasta
`storage/app/public` (imagens de galeria, logos e capas).
