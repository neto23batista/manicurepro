# Bugs e gaps restantes — inventário

**Data:** 2026-08-15 (ciclo 3)  
**Branch:** `cursor/fiscal-empresa-calendar-3015` → merge `main`  
**Método:** docs + CI + Pest local.

---

## Corrigido neste ciclo

| # | Item | Correção |
|---|------|----------|
| 1 | **Laravel 12** | `laravel/framework` ^12.66; Pest 3; `composer audit` limpo |
| 2 | **Deploy staging** | `.github/workflows/deploy-staging.yml` (build/migrate/cache/smoke) |
| 3 | **NF-e provedor** | `NotaFiscalProvider` + Stub + Http; `FISCAL_DRIVER` |
| 4 | **OAuth calendário** | Google/Outlook via `CalendarOAuthService` + perfil + sync |
| 5 | **Multi-empresa base** | `companies` + `saloes.company_id` nullable; `Salao::principal()` intacto |
| 6 | **A11y WCAG pass** | Auth landmarks/ARIA; `x-form-errors`; toggle senha; `A11yAuthTest` |

Ciclos 1–2 (já em `main`): CI Composer/Vite/Pint/PHPStan, Dompdf 3, Web Push auto-UI, gorjeta Pix, API ops RO.

---

## Ainda aberto (escopo explícito / não produto completo)

| Item | Notas |
|------|--------|
| **NF-e SEFAZ nativo** | HTTP provider é integração estilo API; não é emissor SEFAZ embutido. `FISCAL_ENABLED=false` em prod até homologar |
| **Multi-empresa produto** | Foundation só — UI/tenant switch/billing ainda single-tenant |
| **Deploy SSH real** | Workflow é template; falta secret/host do servidor |
| **Spatie / app nativo** | Fora de escopo |

---

## Ops

- Prod: `GORJETA_ONLINE_HABILITADO=false` até validar MP; `FISCAL_ENABLED=false`; configurar `CALENDAR_*` / VAPID conforme PRODUCAO.
- Smoke: `docs/PRODUCAO.md`.

*Atualizar quando o código mudar — não na intenção.*
