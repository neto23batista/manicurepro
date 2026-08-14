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
| `manicure:expirar-pontos-fidelidade` | diário 03:30      | Expira pontos de fidelidade vencidos. |
| `manicure:reativar-inativos`         | segunda 10:00     | Marketing: reativação de inativos (se `marketing.enabled`). |
| `manicure:sugerir-retorno`           | diário 10:30      | Marketing: sugerir retorno (se `marketing.enabled`). |
| `manicure:backup --keep=14`          | diário 02:30      | ZIP do banco + `storage/app/public` em `storage/app/backups/`. |

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
    estornar a cobrança na MP (best-effort). O dono também pode estornar/cancelar
    a cobrança **sem** cancelar o agendamento em `/dono/agendamentos/{id}` (Resumo → Estornar Pix).
- **WhatsApp (Cloud API):** `WHATSAPP_ENABLED=true`, `WHATSAPP_TOKEN`,
  `WHATSAPP_PHONE_NUMBER_ID` e os templates aprovados.
- **Aniversário:** ligado por padrão (`ANIVERSARIO_ENABLED=true`); gera cupom-presente
  (`ANIVERSARIO_CUPOM=true`, `ANIVERSARIO_CUPOM_VALOR=15`).
- **Sentry (APM opcional):** `SENTRY_LARAVEL_DSN=...` — sem DSN, pacote fica no-op.
- **Web Push:** requer `minishlink/web-push` + VAPID; UI só com `WEBPUSH_SUBSCRIBE_UI=true`
  após validar envio ponta a ponta.

## 8. Pós-deploy — verificação

```bash
php artisan manicure:verificar-producao
```

Resolva todos os ✗ (críticos) e revise os ⚠ (avisos) antes de divulgar o site.

**Nota:** o item **Agendador** é sempre aviso (o PHP não consegue provar que o cron do SO está ativo). Confirme com `crontab -l` e `php artisan schedule:list`.

Saúde em runtime (DB, cache, fila, failed jobs): `/admin/saude` (role admin).

### Smoke checklist (pós go-live)

Marque na ordem; alinhe a `FluxoEmpresarioTest` + uso real no salão:

1. `php artisan manicure:verificar-producao` sem erros críticos.
2. Login **dono** e **cliente** (e-mail verificado).
3. Abrir caixa → criar agendamento → iniciar → finalizar comanda → fechar caixa.
4. Cancelar um agendamento e marcar no-show em outro.
5. Atendente: acessa agenda; **403** em `/dono/financeiro`.
6. Se MP ligado: criar Pix de teste, confirmar webhook, e no show do agendamento usar **Estornar Pix** (sem cancelar o atendimento).
7. Confirmar que a fila drena: `php artisan queue:work --once` (ou ver jobs no Supervisor).
8. `php artisan manicure:backup` e conferir ZIP em `storage/app/backups/`; copiar off-server.
9. Abrir `/admin/saude` e conferir DB/cache/fila verdes.

## 9. Backup e restore

### Backup automatizado

O comando empacota banco + `storage/app/public` em um ZIP em
`storage/app/backups/` (mantém os N mais recentes; padrão 14):

```bash
php artisan manicure:backup
php artisan manicure:backup --keep=30
```

- **SQLite:** copia o arquivo do banco.
- **MySQL:** usa `mysqldump` (precisa estar no `PATH` do sistema).
- Requer extensão PHP `zip`.

Já entra no Laravel Schedule (`02:30`, `--keep=14`). Opcionalmente mantenha cron
extra se preferir horários diferentes; em qualquer caso **copie ZIPs para fora do servidor**.

### Restore (manual)

1. Coloque o app em manutenção: `php artisan down`.
2. Pare o worker de fila (`supervisorctl stop manicure-worker`).
3. Extraia o ZIP do backup (contém `database.sqlite` **ou** `database.sql`, e `storage_public/`).
4. **Banco**
   - SQLite: substitua `database/database.sqlite` (ou o path de `DB_DATABASE`) pelo `database.sqlite` do ZIP. Ajuste dono/permissões.
   - MySQL: `mysql -u USER -p DBNAME < database.sql` (destrutivo — substitui os dados).
5. **Arquivos públicos:** copie o conteúdo de `storage_public/` para `storage/app/public/` e confira `php artisan storage:link`.
6. Limpe caches: `php artisan optimize:clear` (ou `config:cache` / `route:cache` / `view:cache` em produção).
7. Suba de novo: `supervisorctl start manicure-worker` e `php artisan up`.

Não há `manicure:restore` automático de propósito — restore é destrutivo e deve ser revisado.

### Web Push / NF-e (honesto)

Web Push: `minishlink/web-push` já no projeto; configure VAPID e só então `WEBPUSH_SUBSCRIBE_UI=true`.
NF-e continua stub local (`FISCAL_ENABLED`) — **não** emite na SEFAZ; mantenha `false` em produção.

Sentry: `sentry/sentry-laravel` no composer; ative com `SENTRY_LARAVEL_DSN` (opcional).
