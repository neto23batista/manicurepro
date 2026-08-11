# ManicurePro — Backlog de melhorias

Classificação **fiél ao código** (2026-08-10, pós Fases 1–10). Detalhe por módulo: [`docs/AUDITORIA.md`](docs/AUDITORIA.md). Prioridades: [`docs/ROADMAP.md`](docs/ROADMAP.md).

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
- **Policies** — Agendamento, Cliente, Cupom, Produto, Galeria, Folga*, Caixa, Despesa, Pacote, ValePresente, NotaFiscal, AuditLog, Feriado, Fornecedor

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
- **Sinal Pix MP** + **Pix total/restante** (controller + config + testes)
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
- Ops: `manicure:backup` + `/admin/saude`
- API v1 polish: erros JSON (`ApiError`), `GET /me/fidelidade`, filtros/paginação em agendamentos

### Qualidade / UX (Fase 10 P2 pontual)

- **Tema `cor_primaria`** do salão aplicado via `theme-vars` (DB → CSS vars; fallback config/env)
- Auth / erros 403–500 **100% Vite** (sem Bootstrap CDN)
- Footer social com URLs de `config('manicure.social.*')` (sem `href="#"` vazio)
- Erros genéricos ao usuário (`HandlesDomainExceptions` — sem vazar `$e->getMessage()`)
- Skip-link em layouts app/auth/público/erros; foco no modal de confirmação
- Loading automático em submits (`btn-loading` em `app.js`)
- Smoke empresário: `FluxoEmpresarioTest` (caixa→agenda→comanda→fechar; cancelamento; no-show; double booking; estoque zerado; atendente 403; IDOR)

---

## PARCIAL

Itens com código, mas incompletos.

| Item | O que existe | O que falta | Arquivos-chave |
|------|--------------|-------------|----------------|
| **Web Push** | Persistência + canal; UI subscribe **escondida** (`WEBPUSH_SUBSCRIBE_UI`) | `sendToUser` stub (log + 0); sem `minishlink/web-push` | `WebPushService.php` |
| **NF-e** | Rascunho local, UI dono (flag `fiscal.enabled`) | Emissão SEFAZ / provedor | `NotaFiscalService.php` |
| **API v1** | Auth + salão/slots + agendamentos + fidelidade + erros JSON | Paridade web (financeiro, estoque, caixa) | `routes/api.php` |
| **Avaliações** | Create web + API | Moderação admin; média na página pública | `Cliente/AgendamentoController::avaliar` |
| **Estorno Pix UX** | `MercadoPagoService::cancelarOuEstornar` | UI dono dedicada de refund | `MercadoPagoService.php` |
| **A11y** | Skip-link + foco modal básico + contraste parcial | Auditoria completa / contraste sistemático | layouts + `confirm-modal` |

---

## FUTURO

Ainda não há implementação utilizável (ou só estratégia em doc).

- [ ] **Emissor fiscal real** (SEFAZ / eNotas etc.) — substituir stub
- [ ] **Web Push send real** — pacote + VAPID operacional ponta a ponta
- [ ] **Sync OAuth** Google/Outlook (hoje só `.ics` + template URL)
- [ ] **Uma UI de booking** — consolidar guest / cliente / dono
- [ ] **Gorjeta via Mercado Pago**
- [ ] **Sentry** opcional (APM); hoje só `report()` + log
- [ ] **Pipeline de deploy** (staging + automático) — hoje manual via PRODUCAO.md
- [ ] **Multi-empresa / filiais** — ver [`docs/ARQUITETURA.md`](docs/ARQUITETURA.md); **não migrar agora**
- [ ] **Spatie Permission** — só se as 5 roles + JSON leve não bastarem
- [ ] App nativo (hoje PWA + API parcial)

---

## Ordem sugerida (pós Fases 1–10)

1. Uso real em salão + correção de bugs operacionais.
2. Push send real **ou** manter UI escondida; NF-e continuar `FISCAL_ENABLED=false` em prod.
3. Moderação de avaliações + média pública (se o salão usar reviews).
4. OAuth / fiscal real / multi-empresa só com escopo explícito.

---

*Atualize só com gaps reais frente ao código — não promover PARCIAL a IMPLEMENTADO sem view/teste/wiring.*
