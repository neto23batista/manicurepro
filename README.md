# ManicurePro — Fernanda Silva Nails

Aplicação **single-tenant** Laravel 11 para o salão **Fernanda Silva Nails**: site público (PWA), painéis por perfil e API REST com Sanctum.

Não é um SaaS multi-salão. A home pública é a página do próprio salão (`Salao::principal()`).

Produção: ver **[docs/PRODUCAO.md](docs/PRODUCAO.md)** (`php artisan manicure:verificar-producao`).  
Docker / Sail (opcional): **[docs/DOCKER.md](docs/DOCKER.md)**.

Backlog restante: **[MELHORIAS.md](MELHORIAS.md)**.

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

---

## O que existe hoje

- **Perfis:** `admin`, `dono`, `atendente`, `manicure`, `cliente`
- **Agendamento online** (exige conta): manicure, serviços, slots, hold temporário de horário
- **Agenda** com conflito, folgas, recorrência (pelo painel do dono), reagendamento, confirmação por link assinado
- **Calendário:** download `.ics` (cliente e export da agenda dono/manicure) + link template do Google Calendar no detalhe do cliente; sync OAuth ainda no backlog
- **Comandas / caixa** (dono): finalização, venda de produto na comanda, cupom, vale-presente
- **Estoque** de produtos + movimentações
- **Fidelidade** com pontos e resgate
- **Cupons**, **lista de espera**, **avaliações**, **galeria** de trabalhos
- **Sinal Pix** opcional (Mercado Pago) + webhook; **WhatsApp Cloud API** opcional (lembretes/confirmações)
- **Relatórios PDF**, dashboard com gráficos
- **Perfil** (avatar, senha), verificação de e-mail, reset de senha, **2FA TOTP**, exportação/exclusão LGPD
- **PWA** (`manifest.json`, service worker, `offline.html`)
- **API `/api/v1`** — ver seção abaixo

### Atendente ≠ dono (financeiro)

Rotas sob `role:dono,atendente` cobrem operação (agenda, clientes, cupons, produtos, galeria, folgas).

**Somente `dono` (admin herda via `RoleMiddleware`):** financeiro, vales-presente e configuração do salão. Atendente recebe **403** nessas rotas.

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

Alternativa ao stack nativo — não substitui a instalação acima. Compose Sail (`docker-compose.yml`: MySQL, Redis, app PHP, Mailpit). Ver **[docs/DOCKER.md](docs/DOCKER.md)**.

---

## Credenciais do seeder

Seeders em `database/seeders/` (`Demo*Seeder`) são **idempotentes** (podem rodar de novo com `php artisan db:seed`). Agendamentos demo só são criados uma vez (marcador interno).

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
| **dono** | Operação + financeiro + vales + configuração do salão |
| **atendente** | Operação do salão **sem** financeiro, vales nem config |
| **manicure** | Dashboard, agenda própria, folgas próprias |
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
```

Login:

```bash
POST /api/v1/login
{ "email": "...", "password": "...", "device_name": "meu-app" }
# → { "token": "..." }
```

Não há endpoint público de “agendar sem login” nem “avaliar” na API v1 atual.

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

Cobertura inclui auth, agendamento, segurança (IDOR API, headers/CSP, webhook MP), acesso do atendente, financeiro, fidelidade, Mercado Pago, etc.

---

## Estrutura (resumo)

```
app/
  Console/Commands/     # lembretes, aniversários, limpeza, verificar-producao
  Http/Controllers/     # Admin, Dono, Manicure, Cliente, Api, Auth, Public
  Models/               # salão, agenda, comanda, fidelidade, galeria, vales…
  Policies/             # Agendamento, Cliente, Cupom (cobertura ainda parcial)
  Services/             # Agenda, Comanda, Estoque, Fidelidade, MercadoPago, …
resources/views/        # Blade por perfil + público
routes/web.php          # site + painéis
routes/api.php          # /api/v1
docs/PRODUCAO.md        # deploy e checklist
docs/DOCKER.md          # Sail / Compose (dev local opcional)
```

---

## Licença

Projeto privado / proprietário.
