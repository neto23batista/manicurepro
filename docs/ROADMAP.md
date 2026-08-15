# Roadmap

**Data:** 2026-08-13 (pós plano de melhoria)  
**Regra:** status baseado no código ([AUDITORIA.md](AUDITORIA.md)), não na intenção da onda.

Legenda de status: `feito` · `parcial` · `pendente` · `adiado`

---

## P0 — Produção / segurança / quebras

| ID | Problema | Solução | Status |
|----|----------|---------|--------|
| P0.1 | IDOR na API de agendamentos | Policy em `Api\AgendamentoController` + testes | **feito** |
| P0.2 | Atendente acessava financeiro/vales/config | Rotas só `role:dono` + menu filtrado | **feito** |
| P0.3 | Webhook MP aberto sem secret | Fail-closed + `ProducaoChecker` | **feito** |
| P0.4 | Painéis sem e-mail verificado | Middleware `verified` | **feito** |
| P0.5 | Menu/rotas de **Despesas** sem Blade | `despesas.blade.php` + fluxo no financeiro | **feito** |
| P0.6 | Rotas Pix **total** sem métodos no controller | `pagamento` / `pagamentoStatus` + `pagamento.total` no config | **feito** |
| P0.7 | `CaixaController::show` sem view `caixa-show` | View + testes `CaixaTest` | **feito** |
| P0.8 | Webhook MP sem idempotência | Tabela `webhook_events` + dedupe; anti-regressão pago→pendente | **feito** |

> P0.1–P0.8 = baseline fechada.

---

## P1 — Produto (ondas Fases 1–9)

| ID | Problema | Solução | Status |
|----|----------|---------|--------|
| P1.1 | Agendamento exigia conta | Guest booking (`StoreGuestAgendamentoRequest`, origem `guest`) | **feito** |
| P1.2 | Pacotes/créditos inexistentes | `Pacote` + `PacoteService` + UI dono | **feito** |
| P1.3 | Comissão só % no cadastro | `ComissaoPagamento` + UI financeiro | **feito** |
| P1.4 | Ficha de unhas só texto solto | Campos + `ClienteFichaHistorico` + rota manicure | **feito** |
| P1.5 | Slots recalculados sempre | Cache + invalidação em `AgendaService` | **feito** |
| P1.6 | Caixa diário (abrir/sangria/fechar) | Models + `CaixaService` + controller + views + testes | **feito** |
| P1.7 | Contas a pagar / despesas | Model + controller + view + fluxo UI | **feito** |
| P1.8 | Cobrança Pix do valor total | Service + config + controller + view + testes | **feito** |
| P1.9 | NF-e real (SEFAZ) | `NotaFiscalProvider` (stub + HTTP); SEFAZ nativo não | **parcial** (provedor feito; SEFAZ nativo não) |
| P1.10 | Web Push de verdade | `minishlink/web-push` + send; UI auto com VAPID (`WEBPUSH_SUBSCRIBE_UI` override) | **feito** (validar VAPID em prod) |
| P1.11 | Policies em todos os recursos sensíveis | Pacote/Vale/NF/AuditLog/Feriado/Fornecedor + grants JSON | **feito** (Spatie adiado) |
| P1.12 | Indicação / no-show | Indicação + contador faltas + alerta config | **feito** |
| P1.13 | Sync OAuth Google/Outlook | OAuth + `.ics` + template URL | **feito** (conectar em Perfil; sync ao criar/alterar) |
| P1.14 | Gorjeta / tip online via MP | Pix pós-conclusão + comanda.gorjeta + `ClienteGorjetaOnlineTest` | **feito** |
| P1.15 | UI única de booking | `booking-form.js` + `_slots_picker` (guest/cliente/dono/reagendar) | **feito** |
| P1.16 | Estorno/refund UX | UI dono no show + audit `pagamento.estornado` + `DonoEstornoPixTest` | **feito** |
| P1.17 | CRM segmentação | `ClienteSegmentacao` + filtros + métricas show + cupom reativação | **feito** |
| P1.18 | Marketing retenção | `manicure:reativar-inativos` / `sugerir-retorno` + listener avaliação + gate `marketing.enabled` | **feito** |
| P1.19 | Dashboard KPIs + CSV | Comparativo vs mês anterior + alertas CRM; CSV em `admin.relatorios` | **feito** |
| P1.20 | Agenda feriados/pausas/encaixe/semana | Fase 2 | **feito** |
| P1.21 | Comissões por serviço + ajustes | Fase 4 | **feito** |
| P1.22 | Estoque fornecedor/inventário/margem | Fase 5 | **feito** |
| P1.23 | Variações + fidelidade níveis + cupons avançados | Fase 6 | **feito** |
| P1.24 | Audit UI + permissions JSON + onboarding | Fase 8 | **feito** |
| P1.25 | API polish + backup + `/admin/saude` | Fase 9 | **feito** |

**Ainda falta (honestamente):** SEFAZ nativo (além do HTTP provider); multi-empresa produto (além da foundation); SSH real no deploy staging.

---

## P2 — Qualidade / UX / performance (Fase 10)

| ID | Problema | Solução | Status |
|----|----------|---------|--------|
| P2.1 | Tema pink fixo vs `cor_primaria` do salão | `theme-vars` lê DB do salão (+ fallback env) | **feito** |
| P2.2 | Auth/erros ainda CDN Bootstrap | Unificado no Vite | **feito** |
| P2.3 | Footer social `href="#"` | `config('manicure.social.*')` + normalização de URL | **feito** |
| P2.4 | Erros com `$e->getMessage()` ao usuário | `HandlesDomainExceptions` + mensagens genéricas | **feito** (Sentry opcional via DSN) |
| P2.5 | API v1 fina vs web | Erros JSON + `/me/fidelidade` + filtros + financeiro/estoque/caixa RO | **parcial** (reads ops feitos; writes não) |
| P2.6 | Avaliações sem moderação/listagem | Admin + média na página pública | **feito** |
| P2.7 | `FinanceiroService::fluxoCaixa` sem UI | Exposto no painel financeiro | **feito** |
| P2.8 | PHPStan / build no CI | Jobs no `ci.yml` | **feito** |
| P2.9 | Docker Compose versionado | `docker-compose.yml` + [DOCKER.md](DOCKER.md) | **feito** |
| P2.10 | Skip-link / foco modal / loading submits | + contraste + `:focus-visible` + slots/Pix ARIA + auth ARIA/`form-errors` | **feito** (passada WCAG prática; auditoria formal externa opcional) |
| P2.11 | Validação fluxo empresário E2E | `FluxoEmpresarioTest` + suíte regressão | **feito** |

---

## P3 — Plataforma / futuro

| ID | Problema | Solução | Status |
|----|----------|---------|--------|
| P3.1 | Deploy manual | Workflow staging (template); SSH host ainda manual | **parcial** |
| P3.2 | A11y completa (contraste sistemático, auditoria) | Passada auth/Pix/skip-link; auditoria formal externa opcional | **feito** (prática) |
| P3.3 | Multi-empresa / filiais | Foundation `companies` + `company_id`; produto single-tenant | **parcial** |
| P3.4 | Spatie Permission | Só se 5 roles + JSON não bastarem | **adiado** |
| P3.5 | Emissor fiscal real (eNotas etc.) | `HttpNotaFiscalProvider` + stub; SEFAZ nativo não | **parcial** |
| P3.6 | App mobile nativo | Hoje PWA + API parcial | **adiado** |

---

## Ordem sugerida (pós Fase 10)

1. Uso real em salão + hotfixes (smoke em [PRODUCAO.md](PRODUCAO.md)).
2. Em prod: `FISCAL_ENABLED=false`; configurar OAuth calendário / VAPID; `GORJETA_ONLINE_HABILITADO` só após validar MP.
3. Multi-empresa produto e SSH real de deploy só com escopo explícito.

---

*Atualize status aqui quando o código mudar — não quando o agente “prometer”.*
