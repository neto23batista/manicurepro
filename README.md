# ManicurePro — Fernanda Silva Nails

Aplicação **single-tenant** Laravel 11 para o salão **Fernanda Silva Nails**: site público (PWA), painéis por perfil e API REST com Sanctum.

Não é um SaaS multi-salão. A home pública é a página do próprio salão (`Salao::principal()`).

| Doc | Conteúdo |
|-----|----------|
| **[docs/AUDITORIA.md](docs/AUDITORIA.md)** | Mapa honesto por módulo (FULL / PARCIAL / STUB) |
| **[docs/ARQUITETURA.md](docs/ARQUITETURA.md)** | Single-tenant hoje + estratégia futura multi-empresa (sem migrar) |
| **[docs/SEGURANCA.md](docs/SEGURANCA.md)** | Postura atual + gaps |
| **[docs/ROADMAP.md](docs/ROADMAP.md)** | P0–P3 com problema / solução / status |
| **[MELHORIAS.md](MELHORIAS.md)** | Backlog IMPLEMENTADO / PARCIAL / FUTURO |
| **[docs/PRODUCAO.md](docs/PRODUCAO.md)** | Deploy (`php artisan manicure:verificar-producao`) |
| **[docs/DOCKER.md](docs/DOCKER.md)** | Sail / Compose (opcional) |

---

## Stack

| Camada | Tecnologia |
|--------|------------|
| Backend | PHP 8.2+ / Laravel 11 |
| Banco | MySQL 8 (ou MariaDB 10.6+) |
| Views | Blade + Bootstrap 5.3 |
| Assets | Vite (`npm run dev` / `npm run build`) |
| Auth web | Sessão + Sanctum |
| Auth API | Sanctum (Bearer tokens) |
| PDF | barryvdh/laravel-dompdf |
| Gráficos | ApexCharts (via Vite) |
| Ícones | Font Awesome 6 (via Vite) |
| Testes | Pest 2.x |
| CI | Pest + Pint + PHPStan + build frontend (`.github/workflows/ci.yml`) |

---

## O que existe hoje (fiél ao código)

Categorias alinhadas a [MELHORIAS.md](MELHORIAS.md) e [AUDITORIA.md](docs/AUDITORIA.md).

### IMPLEMENTADO

- **Perfis:** `admin`, `dono`, `atendente`, `manicure`, `cliente` (+ grants JSON leves)
- **Agendamento** (conta ou **guest**): manicure, serviços/variações, slots, hold, recorrência (dono), reagendar, confirmação assinada, no-show, feriados, pausas, encaixe, visão semanal
- **Comandas:** finalização, produto na comanda, cupom, vale-presente, gorjeta presencial
- **Financeiro dono:** entradas + **caixa diário** + **despesas** + fluxo + **repasse/comissões** (regras por serviço + ajustes)
- **Estoque** avançado: fornecedor, inventário, perda/consumo/devolução, giro/margem/CSV
- **CRM** segmentação + marketing (reativar/sugerir retorno)
- **Fidelidade** (níveis + expiração) + **indicação**
- **Cupons** avançados, **pacotes**, lista de espera, galeria, ficha de unhas
- **Sinal Pix** + **Pix total** (Mercado Pago) + webhook fail-closed + idempotente + **estorno/cancelamento Pix no painel do dono** (sem cancelar agendamento)
- **WhatsApp Cloud API** opcional
- Relatórios PDF/CSV, dashboards com KPIs/alertas, onboarding, auditoria UI
- Perfil, e-mail verify, reset, **2FA TOTP**, LGPD
- **PWA**, iCal + template Google (sem OAuth), cache de slots
- Ops: backup (`manicure:backup` no schedule), `/admin/saude`, Docker Sail, CI
- **API `/api/v1`** (auth + salão/slots + agendamentos + fidelidade + erros JSON)
- Tema `cor_primaria` do salão no CSS; auth/erros via Vite; skip-link + a11y básica
- **Avaliações** com moderação dono/atendente e média pública só das publicadas
- Booking unificado (`booking-slots.js` + `booking-form.js`) guest/cliente/dono/reagendar
- **Sentry** opcional (`SENTRY_LARAVEL_DSN`)

### PARCIAL

- **Web Push:** send real com `minishlink/web-push` (composer) + VAPID; UI subscribe **escondida** por padrão até validar ponta a ponta
- **NF-e:** rascunho local — **não emite SEFAZ**
- **API:** sem paridade financeira/estoque/caixa
- **A11y:** skip-link + foco modal + contraste/`focus-visible` nos fluxos críticos; auditoria completa pendente

### FUTURO

- Sync OAuth de calendário (Google/Outlook)
- Emissor fiscal real (SEFAZ / provedor)
- Multi-empresa / filiais ([ARQUITETURA.md](docs/ARQUITETURA.md) — estratégia apenas)
- API mobile completa; pipeline de deploy
- Gorjeta / tip via Mercado Pago
- Spatie Permission full; app nativo

### Atendente ≠ dono (financeiro)

Rotas sob `role:dono,atendente` cobrem operação (agenda, clientes, cupons, pacotes, produtos, galeria, folgas).

**Somente `dono` (admin herda via `RoleMiddleware`):** financeiro, caixa operacional, despesas, vales-presente, NF-e stub e configuração do salão. Atendente recebe **403** nessas rotas.

---

## Instalação (local)

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* no .env
php artisan migrate --seed
php artisan storage:link
npm ci && npm run build   # ou: npm run dev
php artisan serve
```

Acesse `http://localhost:8000`.

### Docker / Sail (opcional)

Compose versionado (`docker-compose.yml`: MySQL, Redis, app PHP, Mailpit). Ver **[docs/DOCKER.md](docs/DOCKER.md)**.

---

## Credenciais do seeder

Seeders em `database/seeders/` (`Demo*Seeder`) são **idempotentes**. Agendamentos demo só são criados uma vez (marcador interno).

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | `admin@fernandasilvanails.com` | `admin123` |
| Dono | `fernanda@fernandasilvanails.com` | `dono123` |
| Atendente | `atendente@fernandasilvanails.com` | `atendente123` |
| Manicure | `fernanda.profissional@fernandasilvanails.com` | `manicure123` |
| Manicure | `camila@fernandasilvanails.com` | `manicure123` |
| Manicure | `juliana@fernandasilvanails.com` | `manicure123` |
| Cliente | `cliente@fernandasilvanails.com` | `cliente123` |

Site público: `/` (página do salão `fernanda-silva-nails`).

Cupons demo: `BEMVINDA`, `FIDELIDADE10`, `DESCONTO20`, `ANIVERSARIO`.

---

## Serviços seedados (referência)

| Serviço | Preço | Duração |
|---------|-------|---------|
| Manicure Simples | R$ 30 | 30 min |
| Pedicure | R$ 40 | 45 min |
| Manicure + Pedicure | R$ 65 | 75 min |
| Esmaltação em Gel | R$ 55 | 60 min |
| Alongamento em Fibra | R$ 120 | 120 min |
| Alongamento em Gel | R$ 130 | 120 min |
| Nail Art (por unha) | R$ 10 | 30 min |
| Remoção de Gel/Alongamento | R$ 35 | 30 min |
| Spa dos Pés | R$ 55 | 60 min |
| Blindagem | R$ 45 | 45 min |
| Combo Premium | R$ 150 | 120 min |

---

## Perfis e acesso

| Perfil | Acesso |
|--------|--------|
| **admin** | Painel admin (salão único, manicures, serviços, categorias, usuários, relatórios). Herda operação de dono/atendente; **não** herda rotas de cliente/manicure. |
| **dono** | Operação + financeiro + caixa/despesas (UI parcial) + vales + NF stub + config |
| **atendente** | Operação do salão **sem** financeiro, caixa, despesas, vales nem config |
| **manicure** | Dashboard, agenda própria, folgas, ficha de unhas no atendimento |
| **cliente** | Agendar, histórico, iCal, sinal, lista de espera, fidelidade, avaliações |

Painéis autenticados usam middleware `verified` (e-mail confirmado).

---

## API REST (`/api/v1`)

Fonte de verdade: `routes/api.php`.

**Públicas** (throttle 60/min; login 5/min):

```
POST /api/v1/login
GET  /api/v1/saloes
GET  /api/v1/saloes/{slug}
GET  /api/v1/saloes/{slug}/servicos
GET  /api/v1/saloes/{slug}/manicures
GET  /api/v1/saloes/{slug}/slots
```

**Autenticadas** (Sanctum + throttle 120/min):

```
GET  /api/v1/me
POST /api/v1/logout
GET  /api/v1/agendamentos
POST /api/v1/agendamentos
GET  /api/v1/agendamentos/slots
GET  /api/v1/agendamentos/{id}
POST /api/v1/agendamentos/{id}/cancelar
POST /api/v1/agendamentos/{id}/avaliar
```

Login:

```bash
POST /api/v1/login
{ "email": "...", "password": "...", "device_name": "meu-app" }
# → { "token": "..." }
```

Agendamento **guest** existe na web (`/salao/{slug}/agendar`), não como endpoint dedicado na API v1.

---

## Comandos e cron

```bash
php artisan manicure:enviar-lembretes 24h   # ou 2h
php artisan manicure:enviar-aniversarios
php artisan manicure:limpar-expirados
php artisan manicure:verificar-producao
```

Em produção: cron `* * * * * php artisan schedule:run` + worker de fila (`queue:work`). Detalhes em [docs/PRODUCAO.md](docs/PRODUCAO.md).

---

## Testes

```bash
php artisan test
# ou
./vendor/bin/pest
```

Base sólida pré-onda: ~470 Pest verdes (auth, agenda, segurança IDOR/CSP/webhook, atendente, financeiro, fidelidade, MP sinal, etc.). Suite atual inclui também guest, pacotes, ficha, NF stub, push subscription, honeypot/audit.

---

## Estrutura (resumo)

```
app/
  Console/Commands/     # lembretes, aniversários, limpeza, verificar-producao
  Http/Controllers/     # Admin, Dono, Manicure, Cliente, Api, Auth, Public
  Models/               # salão, agenda, comanda, caixa, despesa, fidelidade, …
  Policies/             # Agendamento, Cliente, Cupom, Produto, Folga*, Galeria, Caixa, Despesa
  Services/             # Agenda, Comanda, Caixa, Estoque, Financeiro, MP, NF stub, WebPush stub, …
resources/views/        # Blade por perfil + público
routes/web.php          # site + painéis
routes/api.php          # /api/v1
docs/                   # AUDITORIA, ARQUITETURA, SEGURANCA, ROADMAP, PRODUCAO, DOCKER
```

---

## Licença

Projeto privado / proprietário.
