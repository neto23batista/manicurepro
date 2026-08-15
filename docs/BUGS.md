# Bugs e gaps restantes — inventário

**Data:** 2026-08-15  
**Base:** `main` @ `b1f985b` + correções CI nesta PR  
**Método:** docs (`AUDITORIA`/`ROADMAP`/`MELHORIAS`) + histórico CI GitHub Actions + suite Pest local.

---

## Corrigido nesta PR (bloqueadores reais)

| # | Problema | Impacto | Correção |
|---|----------|---------|----------|
| 1 | `composer.lock` puxava `symfony/options-resolver` **v8.1** (PHP ≥ 8.4) via Sentry | CI Pest/Pint/PHPStan **não instalava** deps em PHP 8.2/8.3 | Pin `symfony/options-resolver:^7.2` + lock atualizado |
| 2 | Pest sem `public/build/manifest.json` | **135** testes Feature com HTTP 500 (`ViteManifestNotFoundException`) | Stub de manifest em `Tests\TestCase` |
| 3 | `ProducaoCheckerTest` poluído por ZIP de `BackupCommandTest` | Falso negativo no check de backup | Limpa ZIPs antes do assert |
| 4 | Pint: ~170 issues de estilo | Job CI vermelho | `pint` aplicado |
| 5 | PHPStan level 5: **33 erros** (nullsafe, `abort_unless` com model\|null, PHPDoc) | Job CI vermelho + contratos de tipo errados | Correções pontuais sem suppressions |

Estado local pós-fix: **Pest 602 passed**, **Pint OK**, **PHPStan 0 errors**.

---

## Ainda aberto (dívida / produto — não regressão do núcleo)

### Qualidade / supply chain

| Item | Severidade | Notas |
|------|------------|-------|
| **Advisories Composer** (dompdf &lt; 3.1.6) | Média | `barryvdh/laravel-dompdf` ^2 ainda amarra Dompdf 2.x. Avaliar upgrade controlado. |
| **CI: jobs PHP não fazem `npm run build`** | Baixa (mitigado) | Stub resolve testes; prod/dev ainda precisam build real. |

### Produto incompleto (roadmap)

| Item | Status doc | Risco operacional |
|------|------------|-------------------|
| **NF-e SEFAZ** | STUB | Com `FISCAL_ENABLED=true` parece emissão real — manter **false** em prod |
| **Web Push UI** | PARCIAL | Send real; UI off (`WEBPUSH_SUBSCRIBE_UI=false`) até validar VAPID |
| **API v1** | PARCIAL | Sem financeiro/estoque/caixa |
| **Gorjeta online MP** | AUSENTE | Só presencial na comanda |
| **OAuth Google/Outlook** | AUSENTE | Só `.ics` + template URL |
| **A11y WCAG completa** | PARCIAL | Skip-link/foco/contraste feitos |
| **Multi-empresa / Spatie / app nativo / deploy pipeline** | Adiado | Fora do escopo atual |

### P0 de segurança / produto (já fechados)

IDOR API, atendente no financeiro, webhook MP fail-closed + idempotência, painéis `verified`, Pix total, caixa/despesas UI, estorno Pix dono — **não reabrir**.

---

## Ordem sugerida para o próximo ciclo

1. Smoke manual no salão (`docs/PRODUCAO.md`) + avaliar upgrade Dompdf.  
2. Só então: validar VAPID/push UI, ou fiscal real / OAuth com escopo explícito.

---

*Atualizar este arquivo quando um item mudar no código — não na intenção.*
