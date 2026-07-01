# 💅 ManicurePro

Sistema web completo para gestão de salões de manicure e pedicure.  
Desenvolvido com **Laravel 11**, **MySQL 8**, **Blade**, **Sanctum** e tema rosa.

---

## ✨ Funcionalidades

- **Multi-perfil**: admin, dono de salão, atendente, manicure, cliente
- **Agendamento online** com seleção de manicure, serviços e horários disponíveis
- **Gestão de agenda** com detecção de conflitos em tempo real
- **Comandas e pagamentos** com finalização atômica via `DB::transaction()`
- **Programa de fidelidade** com pontos por atendimento
- **Cupons de desconto** percentual e fixo com validade
- **Relatórios em PDF** via dompdf com faturamento e ticket médio
- **Dashboard** com gráficos ApexCharts (agendamentos + faturamento)
- **Controle de estoque** de produtos
- **Avaliações** de clientes por agendamento
- **Lembretes automáticos** por e-mail (comando agendado)
- **PWA** (Progressive Web App) — funciona offline
- **API REST** com autenticação Sanctum para apps mobile

---

## 🗂️ Arquitetura

### Stack
| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.2+ / Laravel 11 |
| Banco de dados | MySQL 8 |
| Templates | Blade + Bootstrap 5.3 |
| Autenticação web | Laravel Sanctum (session) |
| Autenticação API | Laravel Sanctum (tokens) |
| PDF | barryvdh/laravel-dompdf 2.2 |
| Gráficos | ApexCharts (CDN) |
| Ícones | Font Awesome 6 (CDN) |
| Testes | Pest 2.x |

### Estrutura de diretórios
```
manicurepro/
├── app/
│   ├── Console/Commands/     # EnviarLembretes, LimparAgendamentosExpirados
│   ├── Http/
│   │   ├── Controllers/      # Admin, Dono, Manicure, Cliente, Api, Auth
│   │   └── Middleware/       # RoleMiddleware, CheckSalao
│   ├── Models/               # 21 models Eloquent
│   ├── Notifications/        # AgendamentoConfirmado, AgendamentoCancelado, AgendamentoLembrete
│   └── Services/             # AgendaService.php
├── database/
│   ├── factories/            # 5 factories para testes
│   ├── migrations/           # 26 migrações
│   └── seeders/              # DatabaseSeeder com dados realistas
├── public/
│   ├── css/app.css           # Tema rosa completo
│   ├── js/app.js             # Scripts globais
│   ├── sw.js                 # Service Worker (PWA)
│   ├── manifest.json         # PWA manifest
│   └── offline.html          # Página offline
├── resources/views/          # Blade views por perfil
├── routes/
│   ├── web.php               # Rotas web com middleware
│   └── api.php               # API REST
└── tests/Feature/            # AuthTest, AgendamentoTest, PublicTest
```

---

## 🚀 Instalação

### Pré-requisitos
- PHP 8.2+
- Composer
- MySQL 8+
- Node.js (opcional, para assets)

### Passos

```bash
# 1. Clone o repositório
git clone <url-do-repositorio> manicurepro
cd manicurepro

# 2. Instale as dependências PHP
composer install

# 3. Configure o ambiente
cp .env.example .env
php artisan key:generate

# 4. Configure o banco de dados no .env
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=manicurepro
# DB_USERNAME=root
# DB_PASSWORD=sua_senha

# 5. Execute as migrações e seeders
php artisan migrate --seed

# 6. Crie o link de storage
php artisan storage:link

# 7. Inicie o servidor
php artisan serve
```

Acesse: `http://localhost:8000`

---

## 🔑 Credenciais de Teste

| Perfil | E-mail | Senha |
|--------|--------|-------|
| Admin | admin@manicurepro.com | admin123 |
| Dono / Atendente | dono@manicurepro.com | admin123 |
| Manicure (Ana) | ana@manicurepro.com | manicure123 |
| Manicure (Bia) | bia@manicurepro.com | manicure123 |
| Manicure (Carla) | carla@manicurepro.com | manicure123 |
| Cliente | cliente@manicurepro.com | cliente123 |

---

## 💇 Serviços Padrão

| Serviço | Preço | Duração |
|---------|-------|---------|
| Manicure Simples | R$ 25,00 | 30 min |
| Pedicure Completa | R$ 35,00 | 45 min |
| Manicure + Pedicure | R$ 55,00 | 75 min |
| Unhas em Gel | R$ 45,00 | 60 min |
| Unhas Acrílicas | R$ 80,00 | 90 min |
| Nail Art | R$ 60,00 | 60 min |
| Remoção de Gel/Acrílico | R$ 30,00 | 30 min |
| Spa dos Pés | R$ 50,00 | 60 min |
| Blindagem de Unhas | R$ 40,00 | 45 min |
| Combo Premium | R$ 120,00 | 120 min |

---

## 🗃️ Banco de Dados (26 tabelas)

```
users                          cupons
saloes                         sessions
configuracoes_salao            cache / cache_locks
manicures                      jobs / job_batches / failed_jobs
categorias_servico             personal_access_tokens
servicos
agendamentos
agendamento_servicos
horarios_funcionamento
disponibilidades_manicure
folgas
folgas_manicure
clientes
produtos
estoque_movimentacoes
comandas
comanda_itens
pagamentos
avaliacoes
notificacoes
fidelidade_pontos
```

---

## 🔐 Perfis e Permissões

| Perfil | Acesso |
|--------|--------|
| **admin** | Tudo — painel global, todos os salões, usuários |
| **dono** | Seu salão — agenda, manicures, serviços, relatórios |
| **atendente** | Igual ao dono, sem gerenciamento financeiro |
| **manicure** | Sua agenda, perfil, atendimento |
| **cliente** | Agendar, histórico, avaliações, programa fidelidade |

> **Regra:** `admin` engloba automaticamente permissões de `dono` no `RoleMiddleware`.

---

## 📅 AgendaService — Lógica de Slots

O `AgendaService` gera horários disponíveis respeitando:
1. Horário de funcionamento do salão
2. Disponibilidade individual da manicure
3. Agendamentos já existentes (sem conflito de horário)
4. Folgas do salão e da manicure
5. Intervalo configurável entre atendimentos

Ao **criar** um agendamento, a verificação de conflito é feita novamente dentro de `DB::transaction()` para evitar condições de corrida.

---

## 🧪 Testes

```bash
# Rodar todos os testes
php artisan test

# Ou com Pest diretamente
./vendor/bin/pest

# Com cobertura de código
./vendor/bin/pest --coverage
```

**Suites disponíveis:**
- `AuthTest` — login, logout, registro, throttle
- `AgendamentoTest` — slots, conflito, criação, finalização com comanda
- `PublicTest` — homepage, página do salão, API pública

---

## ⏰ Comandos Agendados

```bash
# Enviar lembretes de agendamentos do dia seguinte
php artisan manicure:enviar-lembretes

# Limpar agendamentos expirados (aguardando > 24h)
php artisan manicure:limpar-expirados
```

Configure o cron do sistema para rodar `php artisan schedule:run` a cada minuto:
```
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📱 PWA (Progressive Web App)

O sistema pode ser instalado como app no celular:
1. Abra o site no Chrome (Android) ou Safari (iOS)
2. Toque em "Adicionar à tela inicial"
3. Use offline com cache de páginas principais

---

## 🌐 API REST

Base URL: `/api/v1/`

**Autenticação:**
```bash
POST /api/v1/login
{ "email": "...", "password": "...", "device_name": "meu-app" }
# Retorna: { "token": "..." }
```

**Endpoints públicos:**
```
GET  /api/v1/saloes                    # Lista salões
GET  /api/v1/saloes/{slug}             # Detalhes do salão
GET  /api/v1/saloes/{slug}/servicos    # Serviços disponíveis
GET  /api/v1/saloes/{slug}/manicures   # Profissionais
POST /api/v1/saloes/{slug}/slots       # Horários disponíveis
POST /api/v1/saloes/{slug}/agendar     # Criar agendamento
```

**Endpoints autenticados (Bearer token):**
```
GET  /api/v1/me                        # Perfil do usuário
GET  /api/v1/meus-agendamentos         # Histórico
POST /api/v1/agendamentos/{id}/cancelar
POST /api/v1/agendamentos/{id}/avaliar
```

---

## 🎨 Tema Visual

| Propriedade | Valor |
|-------------|-------|
| Cor primária | `#e91e8c` (rosa) |
| Gradiente | `135deg, #e91e8c → #ff6bb5` |
| Fonte | Arial / system-ui |
| Framework CSS | Bootstrap 5.3.2 |

---

## 📄 Licença

Este projeto é privado e proprietário. Todos os direitos reservados.

---

*ManicurePro — Gestão profissional para salões de manicure* 💅
