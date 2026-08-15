# ManicurePro — Backlog de melhorias

Classificação **fiél ao código** (2026-08-13, pós plano de melhoria). Detalhe por módulo: [`docs/AUDITORIA.md`](docs/AUDITORIA.md). Prioridades: [`docs/ROADMAP.md`](docs/ROADMAP.md).

Produção: [`docs/PRODUCAO.md`](docs/PRODUCAO.md).

---

## IMPLEMENTADO

Não reabrir como backlog.

### Segurança / acesso (P0 baseline)

- **IDOR na API** — `Api\AgendamentoController` + Policy; `ApiAgendamentoIdorTest`
- **Atendente sem financeiro** — `/dono/financeiro*`, vales, config, NF só `role:dono`
- **`RoleMiddleware`** — admin/dono herdam operação; não `cliente`/`manicure`
- **`role_or_perm`** + grants JSON leves em `configuracoes_salao.role_permissions` (sem Spatie)
- **Painéis `verified`**
- **Webhook Mercado Pago fail-closed** sem secret; `ProducaoChecker`
- **Idempotência de webhook MP** — tabela `webhook_events`; duplicata → 200 sem reprocessar; anti-regressão `pago` → `pendente`
- **CSP / SecurityHeaders / honeypot** — `ContentSecurityPolicy`, `SecurityHeaders`, `ProtectPublicForms`
- **`audit_logs`** + UI read-only `/dono/auditoria`
- **Policies** — Agendamento, Cliente, Cupom, Produto, Galeria, Folga*, Caixa, Despesa, Pacote, ValePresente, NotaFiscal, AuditLog, Feriado, Fornecedor, Avaliacao

### Produto núcleo

- Auth completa (reset, verify, 2FA TOTP + recovery codes), perfil/avatar, LGPD export/exclusão
- Agenda (hold, recorrência, reagendar, confirmação signed, no-show, **guest booking**, feriados, pausas, encaixe dono, visão semanal)
- Comanda (produto, vale, gorjeta presencial), cupons avançados, vale-presente
- **Pacotes** (`PacoteController` / `PacoteService`)
- **Comissões / repasses** (`ComissaoPagamento` + regras por serviço %/fixo + ajustes manuais com auditoria)
- **Ficha de unhas** + histórico (`ClienteFichaHistorico`)
- **Indicação** + fidelidade com níveis + expiração de pontos
- Estoque avançado (fornecedor, inventário, perda/consumo/devolução, giro/margem/CSV)
- Galeria, lista de espera, aniversário (comando), iCal + template Google
- **Avaliações** — create web/API (só cliente), moderação dono/atendente (`publicar`), média pública só das publicadas
- **Sinal Pix MP** + **Pix total/restante** (controller + config + testes)
- **Estorno Pix UX** — `POST dono/agendamentos/{id}/estorno-pix` + bloco no show + audit `pagamento.estornado` (`DonoEstornoPixTest`)
- **Caixa operacional** — abrir/movimentar/fechar + show + `CaixaTest`
- **Despesas** + **fluxo de caixa** no painel financeiro
- **CRM** — `ClienteSegmentacao` + filtros + cupom reativação
- **Marketing** — `manicure:reativar-inativos` / `sugerir-retorno` + pedir avaliação (gate `marketing.enabled`)
- **Variações de serviço** no catálogo/booking
- WhatsApp channel opt-in
- **Cache de slots** (`AgendaService` + `AgendaSlotsCacheTest`)
- PWA (manifest/SW/offline), Docker Compose Sail, CI (Pest + Pint + **PHPStan** + **frontend build**)
- Soft deletes; relatórios PDF + CSV admin; dashboard KPIs + alertas
- Onboarding wizard + checklist no dashboard
- Ops: `manicure:backup` (também no schedule 02:30) + `/admin/saude` + smoke checklist em PRODUCAO.md
- API v1 polish: erros JSON (`ApiError`), `GET /me/fidelidade`, filtros/paginação em agendamentos
- **Booking unificado** — `resources/js/booking-form.js` + `_slots_picker` (guest/cliente/dono/reagendar)
- **Sentry** opcional (`config/sentry.php` + `SENTRY_LARAVEL_DSN`)

### Qualidade / UX (Fase 10 P2 pontual)

- **Tema `cor_primaria`** do salão aplicado via `theme-vars` (DB → CSS vars; fallback config/env)
- Auth / erros 403–500 **100% Vite** (sem Bootstrap CDN)
- Footer social com URLs de `config('manicure.social.*')` (sem `href="#"` vazio)
- Erros genéricos ao usuário (`HandlesDomainExceptions` — sem vazar `$e->getMessage()`)
- Skip-link em layouts app/auth/público/erros; foco no modal de confirmação
- Loading automático em submits (`btn-loading` em `app.js`)
- A11y: contraste `.text-muted`, `:focus-visible`, slots ARIA, Pix ARIA, auth `aria-invalid`/`form-errors`, landmarks `<main>`
- Smoke empresário: `FluxoEmpresarioTest`
- **Laravel 12** + Pest 3 (`composer audit` limpo)
- **Calendar OAuth** Google/Outlook (Perfil + sync)
- **Fiscal provider** stub/HTTP
- **companies** foundation
- Deploy staging workflow (template)

---

## PARCIAL

Itens com código, mas incompletos.

| Item | O que existe | O que falta | Arquivos-chave |
|------|--------------|-------------|----------------|
| **Web Push** | `minishlink/web-push` + send real; UI **auto** com VAPID | Validar ponta a ponta em prod | `WebPushService.php` |
| **NF-e** | Provedor stub + HTTP (`FISCAL_DRIVER`) + UI dono | Emissão SEFAZ nativa / homologação | `Services/Fiscal/*` |
| **API v1** | Auth + slots + agendamentos + fidelidade + financeiro/estoque/caixa RO | Writes ops / paridade total | `routes/api.php` |
| **Multi-empresa** | `companies` + `company_id` | Switch tenant / billing / UI | `Company`, migrations |
| **Deploy** | `deploy-staging.yml` smoke | SSH/host secrets reais | `.github/workflows/` |

---

## FUTURO

- [ ] **Emissor fiscal SEFAZ nativo** (além do HTTP provider)
- [x] **Sync OAuth** Google/Outlook
- [x] **Gorjeta via Mercado Pago**
- [x] **Pipeline de deploy** (template staging; SSH real pendente)
- [ ] **Multi-empresa produto** — foundation pronta; ver [`docs/ARQUITETURA.md`](docs/ARQUITETURA.md)
- [ ] **Spatie Permission** — só se as 5 roles + JSON leve não bastarem
- [ ] App nativo (hoje PWA + API parcial)
- [x] **Upgrade Laravel 12**

---

## Ordem sugerida (pós Fases 1–10)

1. Uso real em salão + correção de bugs operacionais (smoke em PRODUCAO.md). Inventário vivo: [`docs/BUGS.md`](docs/BUGS.md).
2. Em prod: `FISCAL_ENABLED=false` até homologar; configurar OAuth/VAPID; gorjeta online só após validar MP.
3. Multi-empresa produto / SSH real só com escopo explícito.

---

*Atualize só com gaps reais frente ao código — não promover PARCIAL a IMPLEMENTADO sem view/teste/wiring.*
