# Auditoria — o que existe de verdade

**Data:** 2026-08-10 (pós Fases 1–10)  
**Repo:** ManicurePro / Fernanda Silva Nails  
**Escopo:** mapa por módulo fiél ao código atual. Não inventa features da onda que falharam ou ficaram pela metade.

**Legenda**

| Status | Significado |
|--------|-------------|
| **FULL** | Fluxo usável ponta a ponta (UI + regras + testes relevantes) |
| **PARCIAL** | Código incompleto: falta view, método, flag de config, ou só schema |
| **STUB** | Explicitamente no-op / rascunho local (documentado no código) |
| **AUSENTE** | Não há implementação |

Base sólida: agenda, comanda, financeiro, caixa/despesas, guest booking, pacotes, CRM, marketing, estoque avançado, PWA, Docker/CI, segurança baseline. Suite Pest grande (`FluxoEmpresarioTest` + ~100 arquivos de teste).

---

## Resumo executivo

O núcleo do produto **está** no código. Fases 1–10 fecharam P0 (Pix total, webhook idempotente, caixa/despesas), agenda avançada, CRM, comissões por serviço, estoque avançado, catálogo/fidelidade, marketing, RBAC/onboarding, API/ops e P2 UX pontual (tema, erros seguros, skip-link).

Buracos honestos que **ainda** restam:

- **NF-e** — stub local, sem SEFAZ.
- **Web Push send** — stub; UI de subscribe escondida (`WEBPUSH_SUBSCRIBE_UI=false`) até haver minishlink + send real.
- API sem paridade financeira/estoque.
- Multi-empresa = futuro (ver ARQUITETURA).

## Mapa por módulo

### Auth — FULL

Login, registro, reset de senha, verificação de e-mail, 2FA TOTP + recovery codes.

- Controllers: `app/Http/Controllers/Auth/*`, `TwoFactorController`
- Service: `app/Services/TotpService.php`
- Testes: `AuthTest`, `PasswordResetTest`, `VerifyEmailTest`, `TwoFactorTest`

### Agendamento (web) — FULL

Hold de slot, conflito, folgas, recorrência (dono), reagendar, confirmação por link `signed`, no-show (`nao_compareceu` + `total_faltas`).

- Controllers: `PublicController`, `Dono/AgendamentoController`, `Cliente/AgendamentoController`, `Manicure/AgendaController`, `ConfirmacaoController`
- Service: `app/Services/AgendaService.php`
- Testes: `AgendamentoTest`, `SlotHoldTest`, `RecorrenteTest`, `ReagendamentoTest`, `ConfirmacaoTest`, `NoShowTest`

### Guest booking — FULL

`POST /salao/{slug}/agendar` **sem** `auth`; `StoreGuestAgendamentoRequest`; `Cliente::findOrCreateGuest`; origem `guest`.

- Arquivos: `PublicController::storeAgendamento`, `tests/Feature/GuestBookingTest.php`
- Migration origem: `database/migrations/2026_08_10_240001_add_guest_to_agendamento_origem.php`

### Comanda operacional — FULL

Fechar comanda, vender produto, vale-presente, gorjeta no fechamento presencial. Cupom entra na criação do agendamento (`AgendaService`), não no `ComandaService`.

- `app/Services/ComandaService.php`, models `Comanda` / `ComandaItem` / `Pagamento`
- Testes: `ComandaProdutoTest`, trechos em `ValePresenteTest` / `MercadoPagoTest` (gorjeta)

### Caixa diário (abrir/fechar/movimentar) — FULL

Service, policy, FormRequests, controller, rotas dono, menu, views index/show e testes.

| Peça | Path | Existe? |
|------|------|---------|
| Models | `app/Models/Caixa.php`, `CaixaMovimentacao.php` | sim |
| Migration | `database/migrations/2026_08_10_231001_create_caixas_tables.php` | sim |
| Service | `app/Services/CaixaService.php` | sim |
| Policy | `app/Policies/CaixaPolicy.php` | sim |
| Controller | `app/Http/Controllers/Dono/CaixaController.php` | sim |
| Rotas | `dono/financeiro/caixa*` em `routes/web.php` | sim |
| View index | `resources/views/dono/financeiro/caixa.blade.php` | sim |
| View show | `resources/views/dono/financeiro/caixa-show.blade.php` | sim |
| Testes dedicados | `tests/Feature/CaixaTest.php` | sim |

> Não confundir com o bloco “Caixa & Comissões” de `FinanceiroService::caixa()` (agregação de pagamentos por forma) — esse relatório **também** está FULL.

### Despesas — FULL

| Peça | Path | Existe? |
|------|------|---------|
| Model | `app/Models/Despesa.php` | sim |
| Migration | `database/migrations/2026_08_10_231002_create_despesas_table.php` | sim |
| Policy | `app/Policies/DespesaPolicy.php` | sim |
| FormRequests | `StoreDespesaRequest`, `UpdateDespesaRequest` | sim |
| Controller | `app/Http/Controllers/Dono/DespesaController.php` | sim |
| Rotas | `dono/financeiro/despesas*` | sim |
| Menu / nav | `Sidebar`, `_nav.blade.php` | sim |
| View | `resources/views/dono/financeiro/despesas.blade.php` | sim |
| Integração UI fluxo | `FinanceiroService::fluxoCaixa()` + painel `dono/financeiro/index` | sim |
| Testes | cobertos em `CaixaTest` (fluxo/despesas) | sim |

### Financeiro / comissões — FULL

Painel dono: entradas por forma + liquidação de comissão (`ComissaoPagamento`) + regras por serviço + ajustes manuais auditados.

- `Dono/FinanceiroController.php`, `FinanceiroService.php`
- Regras: `Servico.comissao_percentual` / `comissao_fixo` sobrepõem `%` da manicure (por linha do atendimento)
- Ajustes: tabela `comissao_ajustes` + `AuditLogger` (`comissao.ajuste` / `comissao.ajuste_removido`)
- Migrations: `2026_08_10_220002_create_comissao_pagamentos_table.php`, `2026_08_10_233001_add_comissao_fixo_to_servicos.php`, `2026_08_10_233002_create_comissao_ajustes_table.php`
- View: `resources/views/dono/financeiro/index.blade.php`
- Testes: `FinanceiroTest.php`, `ComissaoRegrasTest.php`

### Estoque / produtos — FULL

CRUD + movimentação; alertas via `config('manicure.estoque.*')`; event `EstoqueZerado`.
Fornecedores CRUD + `produto.fornecedor_id`; inventário (contagem → ajustes + `AuditLogger`); tipos `perda` / `consumo_interno` / `devolucao` (motivo obrigatório); relatório giro/parados/margem + CSV.

- `Dono/ProdutoController`, `FornecedorController`, `InventarioController`, `EstoqueRelatorioController`
- `EstoqueService`, `ProdutoPolicy`, `FornecedorPolicy`
- Testes: `ProdutoTest`, `ComandaProdutoTest`, `EstoqueAvancadoTest`

### Fidelidade + indicação — FULL

Pontos/resgate + recompensa de indicação em `FidelidadeService` (não há classe `IndicacaoService` separada).

- `Cliente/FidelidadeController`, flags em `config/manicure.php` → `indicacao`
- Testes: `FidelidadeResgateTest`, `IndicacaoFidelidadeTest`

### CRM segmentação — FULL

Segmentos novos / recorrentes / inativos / VIP / risco churn via `ClienteSegmentacao` + `config('manicure.crm.*')`. Listagem com filtro; show com ticket médio, última/próxima visita e LTV; cupom de reativação reusa `Cupom` (não duplica fidelidade).

- `App\Services\ClienteSegmentacao`, `Dono\ClienteController` (filtro + `reativar`)
- Views: `dono/clientes/index|show`, `components/badge-crm`
- Testes: `ClienteSegmentacaoTest`, extensão em `ClienteDonoTest`

### Cupons / vale-presente — FULL

- Cupons: `Dono/CupomController`, `CupomPolicy`, `CupomTest`
- Vales: `Dono/ValePresenteController`, `ValePresentePolicy`, `ValePresenteService`, `ValePresenteTest`

### Pacotes — FULL

CRUD + atribuir ao cliente; `PacotePolicy` + authorize no controller.

- `Pacote`, `ClientePacote`, `PacoteService`, `Dono/PacoteController`, views `dono/pacotes/*`
- Migration: `2026_08_10_000001_create_pacotes_tables.php`
- Teste: `PacoteTest`

### Lista de espera — FULL

`Cliente/ListaEsperaController`, listener/notificação de vaga, `ListaEsperaTest`.

### Avaliações — FULL

Cliente avalia (web + API `POST .../avaliar`, só o cliente dono). Dono/atendente listam e ocultam (`publicar`). Página pública mostra só publicadas; `nota_media` ignora ocultas.

- `Dono/AvaliacaoController`, `AvaliacaoPolicy`, view `dono/avaliacoes/index`
- Testes: `AvaliacaoModeracaoTest`, `ApiAgendamentoAvaliarTest`

### Galeria — FULL

`Dono/GaleriaController`, `GaleriaFotoPolicy`, `ImageOptimizer`, `GaleriaTest`.

### NF-e — STUB

**Não emite SEFAZ.** Só rascunho local com `payload.stub = true`.

- `NotaFiscalService`, `Dono/NotaFiscalController`, `NotaFiscalPolicy`, model `NotaFiscal`
- Flag: `config('manicure.fiscal.enabled')` (`FISCAL_ENABLED`)
- Teste: `NotaFiscalStubTest`
- Menu: label “Notas fiscais (stub)” em `Sidebar.php`

### Web Push — PARCIAL (subscribe UI off / send STUB)

- UI de subscribe **escondida** por padrão (`WEBPUSH_SUBSCRIBE_UI=false`; `WebPushService::envioDisponivel()` exige UI + VAPID + classe `minishlink/web-push`)
- Persistência: rotas `/push-subscriptions` existem, mas o layout não emite meta VAPID sem `envioDisponivel()`
- Envio: `sendToUser` retorna `0` (sem send falso) — pacote Composer de push real **ausente**
- Canal: `App\Notifications\Channels\WebPushChannel`
- Teste: `PushSubscriptionTest` + cobertura em `ApiOpsFase9Test`

### Mercado Pago — FULL (sinal + total; sem gorjeta online)

| Capacidade | Status |
|------------|--------|
| Sinal Pix | FULL (service + UI cliente + webhook) |
| Webhook fail-closed sem secret | FULL (`MercadoPagoWebhookController`) |
| Idempotência webhook | FULL — `webhook_events` (provider + event_id); reserva serializa concorrência; reentrega do mesmo payment_id ainda chama `sincronizarStatus` (pending→approved); sem agendamento libera reserva |
| Anti-regressão pago→pendente | FULL em `MercadoPagoService::aplicarStatus` |
| Pix valor total / restante | FULL — config `pagamento.total`, `pagamento`/`pagamentoStatus`, view + `ClientePagamentoTotalTest` |
| Gorjeta via MP | AUSENTE — gorjeta só no fechar comanda presencial |
| Estorno no service | existe no service; UI de refunds limitada |

### WhatsApp Cloud API — FULL (opt-in)

Canal opcional; sem token/`enabled=false` → no-op. `WhatsAppChannelTest`.

### iCal / Google Calendar template — FULL

`.ics` (cliente, dono, manicure) + link template Google (sem OAuth). `ICalService`, `ICalTest`. Sync OAuth: **AUSENTE**.

### Ficha de unhas / histórico — FULL

Campos no cliente + `ClienteFichaHistorico`; manicure atualiza via `agenda.ficha`.

- Migration: `2026_08_10_220001_add_ficha_unhas_to_clientes.php`
- Teste: `ClienteFichaTest`

### LGPD — FULL

Export JSON + exclusão de conta em `PerfilController`. `LgpdTest`.

### PWA — FULL (install/offline)

`public/manifest.json`, `public/sw.js`, `offline.html`. Push de envio = stub (acima).

### API `/api/v1` — PARCIAL

Fonte: `routes/api.php`.

- Público: login, salão/serviços/manicures/slots
- Auth Sanctum: me/logout, `GET /me/fidelidade`, listar/criar/ver/cancelar/avaliar agendamento
- Listagem de agendamentos: paginação + filtros `status`, `manicure_id`, `de`, `ate`, `per_page`
- Erros JSON padronizados (`message` + `code` [+ `errors`]) via `App\Support\ApiError`
- Sem API de financeiro, estoque, caixa, etc.

### Ops — backup / saúde

- `php artisan manicure:backup` → ZIP em `storage/app/backups/` (DB + `storage/app/public`); restore documentado em [PRODUCAO.md](PRODUCAO.md)
- `/admin/saude` (role admin): DB, cache, fila, failed jobs

CSP, SecurityHeaders, honeypot (`ProtectPublicForms`), `RoleMiddleware` + `role_or_perm`, `audit_logs` + UI `/dono/auditoria`, grants JSON leves (sem Spatie), webhook MP fail-closed, policies em pacotes/vales/NF/caixa/despesas. Onboarding wizard + checklist. Detalhes: [SEGURANCA.md](SEGURANCA.md).

### Admin — FULL

Dashboard, salão único (sem create/destroy), manicures, serviços, categorias, usuários, relatórios PDF (`ReportService` + DomPDF), página `/admin/saude`.

### Cache de slots — FULL

`AgendaService` com `Cache::remember` + invalidação; TTL `manicure.cache_ttl.slots_disponiveis`; `AgendaSlotsCacheTest`.

### Tema `cor_primaria` — FULL

Valor no DB (`ConfiguracaoSalao`) e form do dono; `theme-vars` resolve cor do salão (prop / user / `Salao::principal`) com fallback `config('manicure.tema.cor_primaria')`. Teste: `TemaCssTest`.

### A11y / erros seguros — PARCIAL (básico feito)

Skip-link em app/auth/público/erros; foco no `confirm-modal`; loading em submits; `HandlesDomainExceptions` (sem vazar `getMessage`). Auditoria a11y completa / contraste sistemático / Sentry: ainda aberto.

### Docker / CI — FULL

- `docker-compose.yml` (Sail: app, MySQL, Redis, Mailpit) — [DOCKER.md](DOCKER.md)
- `.github/workflows/ci.yml`: Pest (PHP 8.2/8.3), Pint, PHPStan, `npm ci && npm run build`

---

## Tabela rápida

| Módulo | Status |
|--------|--------|
| Auth | FULL |
| Agendamento | FULL |
| Guest booking | FULL |
| Comanda operacional | FULL |
| Caixa diário | FULL |
| Despesas | FULL |
| Financeiro / comissões | FULL |
| Estoque | FULL |
| Fidelidade + indicação | FULL |
| CRM segmentação | FULL |
| Cupons / vales | FULL |
| Pacotes | FULL |
| Lista de espera | FULL |
| Avaliações | FULL |
| Galeria | FULL |
| NF-e | STUB |
| Web Push | PARCIAL (UI off / send STUB) |
| Mercado Pago | FULL (sinal+total; sem tip online) |
| WhatsApp | FULL (opt-in) |
| iCal | FULL |
| Ficha unhas | FULL |
| LGPD | FULL |
| PWA | FULL |
| API v1 | PARCIAL (fidelidade + filtros + erros JSON) |
| Segurança baseline | FULL |
| Admin / PDF / saúde | FULL |
| Backup | FULL (`manicure:backup` + restore docs) |
| Slot cache | FULL |
| Tema por salão | FULL |
| A11y / erros seguros | PARCIAL (básico Fase 10) |
| Docker / CI | FULL |
| OAuth calendário | AUSENTE |
| Multi-empresa | AUSENTE (ver [ARQUITETURA.md](ARQUITETURA.md)) |
| Fluxo empresário smoke | FULL (`FluxoEmpresarioTest`) |

---

*Atualizar este arquivo quando um módulo mudar de status no código — não no desejo do roadmap.*
