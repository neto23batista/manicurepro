# Bugs e gaps restantes — inventário

**Data:** 2026-08-15 (ciclo 2)  
**Branch:** `cursor/fix-ci-bugs-cd0b`  
**Método:** docs + CI + Pest local.

---

## Corrigido neste ciclo

| # | Item | Correção |
|---|------|----------|
| 1 | CI Composer / Vite / Pint / PHPStan | Ver ciclo 1 nesta PR |
| 2 | **Dompdf advisories** | `barryvdh/laravel-dompdf` ^3 + `dompdf/dompdf` 3.1.6 |
| 3 | **Web Push UI** | Auto com VAPID (omitir `WEBPUSH_SUBSCRIBE_UI`); `false` ainda força off |
| 4 | **Gorjeta online MP** | `GORJETA_ONLINE_HABILITADO`, Pix pós-conclusão, webhook/comanda |
| 5 | **API v1 ops** | `GET /financeiro`, `/estoque`, `/caixa`, `/caixa/{id}` (dono) |
| 6 | **A11y pontual** | `aria-label` / `aria-live` em Pix (sinal/total/gorjeta) |

Estado local: Pest + Pint + PHPStan verdes após as mudanças.

---

## Ainda aberto (adiado / escopo explícito)

| Item | Notas |
|------|--------|
| **NF-e SEFAZ** | Stub local; manter `FISCAL_ENABLED=false` em prod |
| **OAuth Google/Outlook** | Só `.ics` + template URL |
| **Laravel 11 advisories** | Patches oficiais em 12.x — upgrade de major separado |
| **A11y WCAG auditoria completa** | Crítico feito; auditoria formal aberta |
| **Multi-empresa / Spatie / app nativo / deploy pipeline** | Adiado |

---

## Ops

- Prod: `GORJETA_ONLINE_HABILITADO=false` até validar MP; `WEBPUSH_SUBSCRIBE_UI=false` se quiser forçar off mesmo com VAPID.
- Smoke: `docs/PRODUCAO.md`.

*Atualizar quando o código mudar — não na intenção.*
