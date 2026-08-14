# Segurança — postura atual e gaps

**Data:** 2026-08-10 (pós Fases 1–10)  
**Princípio:** descrever o que o código faz hoje; gaps são explícitos, sem checklist de marketing.

Detalhe de módulos: [AUDITORIA.md](AUDITORIA.md). Deploy: [PRODUCAO.md](PRODUCAO.md).

---

## Postura atual (o que existe)

### Headers e CSP

| Controle | Onde | Comportamento |
|----------|------|---------------|
| Security headers | `app/Http/Middleware/SecurityHeaders.php` | anexado ao grupo `web` em `bootstrap/app.php` |
| CSP | `app/Http/Middleware/ContentSecurityPolicy.php` | flags `manicure.security.csp_enabled` / `csp_report_only` |
| Testes | `tests/Feature/SecurityHardeningTest.php` | cobre headers/CSP relevantes |

### AuthN / AuthZ

| Controle | Onde |
|----------|------|
| Sessão web + `verified` nos painéis | `routes/web.php` |
| Sanctum API + `user.active` | `routes/api.php`, `EnsureUserIsActive` |
| Roles | `RoleMiddleware` — admin/dono herdam operação de atendente; **não** herdam rotas cliente/manicure |
| Grants extras | `role_or_perm` + JSON em `configuracoes_salao.role_permissions` (sem Spatie) |
| Atendente ≠ financeiro | rotas `dono/financeiro*`, vales, config, NF-e só `role:dono` |
| 2FA TOTP + recovery codes | `TwoFactorController`, `TotpService` |
| Policies | `Agendamento`, `Cliente`, `Cupom`, `Produto`, `GaleriaFoto`, `Folga`, `FolgaManicure`, `Caixa`, `Despesa`, `Pacote`, `ValePresente`, `NotaFiscal`, `AuditLog`, `Feriado`, `Fornecedor`, `Avaliacao` |
| IDOR API agendamentos | `Api\AgendamentoController` + Policy; `ApiAgendamentoIdorTest` |
| Smoke IDOR / 403 | `FluxoEmpresarioTest`, `AtendenteAcessoTest`, `SecurityHardeningTest` |

### Formulários públicos

| Controle | Onde |
|----------|------|
| Honeypot | `ProtectPublicForms` (grupo `web`) |
| Throttle | agendar público 8/min; holds 20/min; login API 5/min; webhooks 60/min |
| Testes | `HoneypotAndAuditTest` |

### Pagamentos

| Controle | Onde |
|----------|------|
| Webhook MP **fail-closed** | sem `MP_WEBHOOK_SECRET` / config secret → rejeita (não processa) |
| Webhook MP **idempotente** | `webhook_events` unique `(provider, event_id)` serializa entregas; reentrega do mesmo payment_id ainda sincroniza status; sem agendamento libera reserva |
| Anti-regressão status | `aplicarStatus` não rebaixa `pago` → `pendente` |
| CSRF except | só `webhooks/*` em `bootstrap/app.php` |
| Checker produção | `ProducaoChecker` alerta MP/secret em prod |
| Testes | `MercadoPagoTest`, `ClientePagamentoTotalTest`, hardening |

### Erros ao usuário

| Controle | Onde |
|----------|------|
| Mensagens genéricas web/API | trait `HandlesDomainExceptions` nos controllers críticos |
| API JSON | `App\Support\ApiError` (`message` + `code` [+ `errors`]); 5xx sem detalhe interno |
| Testes | `HandlesDomainExceptionsTest`, `ApiAuthPolishTest` |

### Auditoria e LGPD

| Controle | Onde |
|----------|------|
| `audit_logs` | migration `2026_08_10_000020_*`, `AuditLogger`, model `AuditLog` |
| UI read-only | `/dono/auditoria` (dono/admin ou grant) |
| Portabilidade / exclusão | `PerfilController::exportarDados` / `excluirConta` |
| Soft deletes | tabelas críticas (migration `2026_05_30_*`) |

### Outros

- Contas inativas bloqueadas na API (`user.active`).
- Confirmação de presença só com URL assinada (`middleware('signed')`).
- Senhas via Hash Laravel; reset com token dedicado.

---

## Gaps (honestos)

### Autorização

- Policies cobrem os recursos sensíveis (incl. **pacotes, vales, NF-e stub, audit**). Grants extras por role em `configuracoes_salao.role_permissions` (sem Spatie); defaults intactos sem grants.
- Spatie Permission full = adiado.

### Config / segredos

- Credenciais WhatsApp/MP/VAPID são por instalação (`.env`). Em multi-empresa futuro isso vira vazamento cross-tenant se não migrar para config por company (ver [ARQUITETURA.md](ARQUITETURA.md)).

### Web Push

- UI de subscribe desligada por padrão (`WEBPUSH_SUBSCRIBE_UI=false`). Envio real quando `minishlink/web-push` estiver instalado + VAPID + UI. VAPID no `.env` sozinho **não** ativa pedido de permissão no browser.

### NF-e stub

- Com `FISCAL_ENABLED=true` o dono cria rascunhos locais. Risco operacional: alguém achar que emitiu documento fiscal real. UI/menu já diz “stub”; reforçar em produção (`FISCAL_ENABLED=false`).

### Observabilidade

- Sentry opcional via `SENTRY_LARAVEL_DSN` (`config/sentry.php`). Sem DSN, pacote fica no-op. Controllers críticos usam mensagem genérica; logs internos ainda registram `$e->getMessage()` (aceitável).

### API

- Superfície fina, mas qualquer endpoint novo precisa repetir o padrão Policy (o IDOR de agendamento já foi endurecido — não reabrir).
- Sem rate limit diferenciado por rota além dos throttles atuais.

### Dependências / supply chain

- CI roda Pest + Pint + PHPStan + build frontend — bom.
- `composer audit` / Dependabot não estão documentados como gate obrigatório neste repo.

### Infra

- Docker Sail é **dev**. Produção segue [PRODUCAO.md](PRODUCAO.md) (HTTPS, `APP_DEBUG=false`, queue worker, cron).
- Single-tenant: isolamento entre “empresas” = isolamento entre **instalações**, não rows.

---

## Matriz rápida

| Área | Postura | Gap principal |
|------|---------|---------------|
| CSP / headers | OK | validar report-only antes de apertar em prod nova |
| Roles painéis | OK | Spatie só se ACL extrapolar 5 roles + JSON |
| Policies | OK | grants JSON leves; Spatie adiado |
| Webhook MP | OK fail-closed + idempotente | secret obrigatório em prod (checker) |
| Honeypot | OK | — |
| Audit log | OK + UI | falta `company_id` no futuro multi |
| LGPD | OK | revisar retenção de `audit_logs` |
| Erros ao usuário | OK (genéricos) | Sentry opcional |
| Push | Fraco (stub send) | não prometer push real |
| Fiscal | Stub | nunca ligar como “emissão” |
| Caixa/Despesa/Pix total | Auth + UI OK | manter testes de regressão MP |

---

## Checklist mínimo antes de produção

1. `APP_DEBUG=false`, `APP_URL` https, `php artisan manicure:verificar-producao` limpo.
2. `MP_WEBHOOK_SECRET` se MP ligado; senão deixe MP desligado.
3. `FISCAL_ENABLED=false` até existir emissor real.
4. Se Pix total em produção: `PAGAMENTO_TOTAL_HABILITADO=true` + token MP válidos.
5. `WEBPUSH_SUBSCRIBE_UI=false` até send real.
6. Rodar `./vendor/bin/pest` (ou `php artisan test`) na release.

---

*Segurança é postura contínua: cada feature nova deve nascer com Policy + teste de IDOR, não “depois”.*
