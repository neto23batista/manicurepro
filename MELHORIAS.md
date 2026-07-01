# 🌸 ManicurePro — Backlog de Melhorias

Documento vivo com sugestões de evolução do sistema, categorizadas e priorizadas.
Use como referência para sprints futuros.

---

## 🔥 Prioridade Alta — Quick wins (1-3 dias cada)

### Funcionalidades
- [ ] **Recuperação de senha** — fluxo "esqueci minha senha" via e-mail
- [ ] **Confirmação de e-mail** no cadastro (Laravel já tem MustVerifyEmail)
- [ ] **Edição do próprio perfil** (todas as roles devem poder editar nome/email/senha/foto)
- [ ] **Upload de avatar** real (atualmente usa apenas ui-avatars como fallback)
- [ ] **Upload de foto do salão e da manicure** com crop e resize automático
- [ ] **Página "Configurações do salão"** para o dono (horários, fidelidade, antecedência)
- [ ] **CRUD de Cupons** (já existe modelo, falta interface)
- [ ] **CRUD de Folgas/Feriados** do salão e da manicure
- [ ] **CRUD de Categorias de Serviço**
- [ ] **CRUD de Clientes** completo no painel do dono

### UX
- [ ] **Toasts** (alerts dismissíveis no canto) em vez de alerts inline
- [ ] **Loading states** em todos os botões durante submit (`<button disabled>`)
- [ ] **Empty states** desenhados em todas as listas vazias (não só texto)
- [ ] **Skeleton loaders** ao carregar slots de horário
- [ ] **Confirmação modal** (em vez de `confirm()` nativo do browser) para excluir/cancelar
- [ ] **Atalhos de teclado** (`Ctrl+K` para busca, `N` para novo agendamento)
- [ ] **Quick actions** flutuantes (FAB) na agenda da manicure

---

## ⭐ Prioridade Média — Maior impacto (3-7 dias cada)

### Funcionalidades
- [ ] **WhatsApp integration** (envio de confirmação/lembrete via WhatsApp Business API)
- [ ] **Pagamentos online** (integração Mercado Pago / Pagar.me) para reservar com sinal
- [ ] **Galeria de trabalhos** por manicure (portfolio de fotos)
- [ ] **Sistema de stories** estilo Instagram para tendências do salão
- [ ] **Programa de indicação** (cliente indica amiga e ganha pontos)
- [ ] **Pacotes/assinaturas** mensais (cliente paga R$X/mês = 4 manicures)
- [ ] **Vale-presente** (gift card digital)
- [ ] **Agendamento recorrente** (toda quinta às 15h por 3 meses)
- [ ] **Lista de espera** quando horário desejado está ocupado
- [ ] **Notificações push** (PWA Web Push API)
- [ ] **Resgate de pontos de fidelidade** (cliente vê quantos pontos tem e troca por descontos)
- [ ] **Histórico de cores** usadas por cliente (anota cor preferida)
- [ ] **Alerta de aniversário** com cupom automático
- [ ] **Pesquisa de salões por geolocalização** ("salões perto de mim")

### Dashboard / Analytics
- [ ] **Heatmap de horários** mais movimentados (Mon 14h, Tue 16h…)
- [ ] **Taxa de retorno** de clientes (LTV)
- [ ] **Funil de conversão** (visitou site → agendou → compareceu → avaliou)
- [ ] **Comparativo mês a mês** com percentual de crescimento
- [ ] **Top 10 clientes** por gasto, frequência e indicações
- [ ] **Análise de horários com baixa ocupação** (sugerir promoção)

### Multi-salão para dono
- [ ] **Dono pode ter vários salões** (atualmente é 1:1)
- [ ] **Seletor de salão no topbar**

---

## 🛡️ Segurança & Conformidade

- [ ] **Two-factor auth (2FA)** opcional via TOTP (Google Authenticator)
- [ ] **Rate limiting** mais agressivo no login (5 tentativas / 15min por IP)
- [ ] **Política de senha forte** configurável (8+ chars, maiúscula, número, símbolo)
- [ ] **Audit log** (quem alterou o quê, quando)
- [ ] **LGPD**: termos de uso, política de privacidade, consentimento, "esquecer-me"
- [ ] **Backup automático** do banco (cron diário enviando dump para S3)
- [ ] **CSP headers** (Content-Security-Policy)
- [ ] **Honeypot** anti-bot no registro
- [ ] **reCAPTCHA v3** no formulário público de agendamento
- [ ] **Soft deletes** em recursos críticos (saloes, manicures, clientes)
- [ ] **Criptografia em trânsito**: forçar HTTPS via middleware

---

## ⚡ Performance

- [ ] **Cache de configuração do salão** (Redis, TTL 1h)
- [ ] **Cache de slots disponíveis** com invalidação ao criar agendamento
- [ ] **Queue para envio de notificações** (em vez de síncrono)
- [ ] **Eager loading** revisado em todas as queries (resolver N+1)
- [ ] **Índices compostos** em `agendamentos(manicure_id, data_hora_inicio, status)`
- [ ] **Paginação cursor-based** em listagens longas
- [ ] **Lazy load de imagens** com `loading="lazy"` + Intersection Observer
- [ ] **Service Worker** com cache mais agressivo (offline-first)
- [ ] **Compressão Brotli/Gzip** no servidor
- [ ] **CDN** para assets estáticos (Cloudflare/Bunny)
- [ ] **Resize de imagens** server-side com Spatie/Image (gerar webp + multiple sizes)
- [ ] **Bundle do JS público** (concatenar e minificar scripts inline)

---

## 🏗️ Arquitetura & Código

- [ ] **API REST v2** com OpenAPI/Swagger documentado
- [ ] **GraphQL** opcional para apps mobile
- [ ] **Policies do Laravel** para autorização granular (em vez do middleware role)
- [ ] **Events + Listeners** para ações importantes (AgendamentoCriado, Cancelado…)
- [ ] **Jobs em fila** para tarefas pesadas (PDF, e-mail, notificações)
- [ ] **Mailable classes** específicas (atualmente usa Notification genérica)
- [ ] **Spatie/Permissions** para roles granulares (substituir middleware atual)
- [ ] **Testes Pest cobrindo todos os controllers** (atual: 3 arquivos)
- [ ] **Pint** rodando no CI para code style
- [ ] **PHPStan/Larastan** nível 6+ para análise estática
- [ ] **Husky/Lefthook** com hooks de pré-commit (pint + phpstan + tests)
- [ ] **Sentry** para tracking de erros em produção
- [ ] **Telescope** em dev
- [ ] **Migrar para Livewire 3** ou **Inertia + Vue** (modernizar frontend)
- [ ] **Tailwind CSS** em vez de Bootstrap (mais customizável)

---

## 🎨 Design & UX moderno

- [ ] **Modo escuro** com toggle e detecção de `prefers-color-scheme`
- [ ] **Temas personalizáveis** por salão (a cor primária vem do `configuracoes_salao.cor_primaria` e já existe!)
- [ ] **Animações de página** com View Transitions API
- [ ] **Scroll reveal** em landing page (elementos aparecem ao scrollar)
- [ ] **Parallax sutil** na hero
- [ ] **Lottie animations** em empty states e loading
- [ ] **Confetti** ao finalizar primeiro agendamento (lib `canvas-confetti`)
- [ ] **Cursor effects** sutil em CTAs principais
- [ ] **Onboarding interativo** estilo Intro.js para novos donos
- [ ] **Componente "calendar picker" visual** moderno (em vez do input date nativo)
- [ ] **Mapa interativo** (Leaflet/Mapbox) na busca de salões
- [ ] **Compartilhamento** (Web Share API) do salão favorito
- [ ] **PWA install prompt** customizado
- [ ] **Print-friendly** das comandas
- [ ] **Acessibilidade WCAG AA** completa (focus visible, ARIA, contraste, navegação por teclado)
- [ ] **i18n** (português + inglês + espanhol)

---

## 📱 Mobile

- [ ] **App mobile nativo** com React Native ou Flutter (consumindo a API existente)
- [ ] **Notificações push nativas** (FCM/APNs)
- [ ] **Câmera + galeria** para upload direto
- [ ] **Biometria** (Face ID / Touch ID) para login
- [ ] **Modo offline** total com sincronização

---

## 💼 Negócio / Monetização

- [ ] **Planos SaaS** (Free / Pro / Business) com limites diferentes
- [ ] **Trial 30 dias** com onboarding guiado
- [ ] **Integração com calendário** (Google Calendar, Outlook)
- [ ] **Webhooks** para integração com sistemas externos
- [ ] **Marketplace de templates** de comandas, recibos
- [ ] **Cashback** automático em pacotes
- [ ] **NF-e/NFC-e** automática (integração eNotas)
- [ ] **Conta digital integrada** (PJ Bank, Stripe)
- [ ] **Indicadores fiscais** (DAS, MEI, Simples)

---

## 🚀 DevOps

- [ ] **GitHub Actions** com CI/CD (test + deploy)
- [ ] **Docker Compose** para dev local consistente
- [ ] **Deploy automático** em VPS via SSH (Deployer/Envoyer)
- [ ] **Staging environment** espelhando produção
- [ ] **Migrations zero-downtime**
- [ ] **Healthcheck endpoint** (`/up`)
- [ ] **Métricas** (Prometheus + Grafana)
- [ ] **Logs estruturados JSON** (em vez de plain text)
- [ ] **APM** (New Relic / Datadog)
- [ ] **Cloudflare** na frente (DDoS, WAF, cache)
- [ ] **Disaster recovery plan** documentado

---

## 📊 Métricas para acompanhar pós-lançamento

| Métrica | Meta inicial |
|---------|--------------|
| Tempo médio de agendamento (cliente) | < 60s |
| Taxa de conversão visita → agendamento | > 8% |
| NPS (clientes finais) | > 50 |
| NPS (donos de salão) | > 60 |
| Churn mensal (donos) | < 5% |
| Uptime | 99.5% |
| Time to first byte (TTFB) | < 200ms |
| Lighthouse Performance | > 90 |
| Lighthouse Accessibility | > 95 |

---

## 🎯 Roadmap sugerido (90 dias)

### Mês 1 — Foundations
- Recuperação de senha, upload de avatar, CRUD de cupons/folgas/clientes, toasts, modais

### Mês 2 — Engajamento
- WhatsApp integration, programa de indicação, vale-presente, lista de espera, push notifications

### Mês 3 — Escala
- Pagamentos online, modo escuro, multi-salão, dashboard analytics avançado, testes 80% cobertura

---

*Mantenha este documento atualizado conforme novas ideias surgem.*
