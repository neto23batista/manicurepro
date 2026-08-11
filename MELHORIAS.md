# ManicurePro — Backlog de melhorias

Só o que **ainda falta**. Itens já no código (reset de senha, verificação de e-mail, 2FA, perfil/avatar, config do salão, cupons, folgas, categorias, clientes, WhatsApp, sinal Mercado Pago, lista de espera, vale-presente, recorrência, fidelidade/resgate, aniversário, CSP/headers, soft deletes, iCal, toasts, CI/Pint, LGPD, confirmação por link assinado, slot hold, etc.) **não** entram aqui.

Produção: [`docs/PRODUCAO.md`](docs/PRODUCAO.md).

---

## Feito recentemente — P0 segurança

Não reabrir como backlog. Já entregue / endurecido:

- **IDOR na API** — `Api\AgendamentoController` usa Policy (`view` / `cancel`); cliente A não vê/cancela agendamento de B
- **Atendente sem financeiro** — rotas `/dono/financeiro`, `/dono/vales`, `/dono/configuracao` só `role:dono`; menu do atendente sem esses itens
- **`RoleMiddleware`** — admin/dono herdam operação (atendente), **não** `cliente`/`manicure`
- **Painéis com `verified`** — e-mail confirmado obrigatório
- **Webhook Mercado Pago fail-closed** sem `MP_WEBHOOK_SECRET`; `ProducaoChecker` alerta em produção
- Testes: `ApiAgendamentoIdorTest`, `AtendenteAcessoTest`, `SecurityHardeningTest`, `RoleMiddlewareTest`

**Ainda em aberto (P1):** unificar autorização no restante do app (Policies + FormRequest) — Policies existem só para Agendamento/Cliente/Cupom; folgas, produtos, galeria etc. ainda misturam `AuthorizesSalao` / `abort(403)`.

---

## P1 — Produto e arquitetura

- [ ] **Unificar autorização** — Policies + FormRequest `authorize()` em todos os recursos sensíveis; opcionalmente **Spatie Permission** se precisar de ACLs além das 5 roles atuais
- [ ] **Agendamento guest** — `public/agendar` ainda exige login/registro; OTP/telefone ou magic link, conta opcional depois
- [ ] **Pagamento completo** — hoje só sinal Pix (`MercadoPagoService`); falta cobrança do valor total, gorjeta, estorno/UI de refunds (ver nota em `docs/PRODUCAO.md`)
- [ ] **Ficha do cliente / histórico de cores** — há `alergias`/`observacoes` genéricos; falta ficha de unhas/cores estruturada
- [ ] **Pacotes / assinaturas** — `servicos.combo` ≠ membership; falta plano mensal com créditos
- [ ] **Repasse de comissão** — % existe no cadastro da manicure; falta liquidação/payout
- [ ] **NF-e / NFC-e** — integração fiscal (ex.: eNotas)
- [ ] **Sync OAuth de calendário** — export `.ics` (cliente + agenda dono/manicure dia/intervalo) e link “Google Calendar” (template URL, sem OAuth) já existem; falta sync bidirecional com OAuth (Google/Outlook)
- [ ] **Web Push** — PWA existe; falta Web Push API (lembretes no dispositivo)

---

## P2 — Performance / UX / qualidade

- [ ] **Cache de slots** — `AgendaService` recalcula com queries em loop; cache curto por manicure+data+duração, invalidar em create/cancel/folga
- [ ] **Uma UI de booking** — consolidar `public/agendar`, `cliente/agendamentos/create` e `dono/agendamentos/create` (+ reagendar)
- [ ] **Tema `cor_primaria`** — valor no DB existe; CSS ainda é pink fixo em grande parte
- [ ] **Auth/erros no Vite** — layouts de auth e páginas de erro ainda usam Bootstrap via CDN além do Vite
- [ ] **Links sociais do footer** — `public-footer` com `href="#"`
- [ ] **Erros seguros** — evitar devolver `$e->getMessage()` ao usuário; log estruturado (Sentry opcional)
- [ ] **API v1 mais completa** — sem agendar/avaliar públicos; superfície mobile ainda fina vs. web

---

## P3 — DevOps / acessibilidade

- [ ] **Docker Compose** — Sail no Composer; sem compose versionado no repo
- [ ] **PHPStan no CI** — `phpstan.neon` existe; pacote/job ainda fora do pipeline (hoje: Pest + Pint)
- [ ] **Build de assets no CI** — `npm ci && npm run build` ausente do workflow
- [ ] **Pipeline de deploy** — staging + deploy automático; hoje manual via `docs/PRODUCAO.md`
- [ ] **A11y** — skip-to-content, foco em modais, auditoria de contraste (já há `aria-*` pontuais e `prefers-reduced-motion`)

---

## Ordem sugerida

1. Guest booking + consolidar UI de agendamento  
2. Policies unificadas (e Spatie só se a hierarquia de roles não bastar)  
3. Cache de slots  
4. Pagamento total / fiscal conforme monetização  
5. Docker + PHPStan/assets no CI + deploy  
6. A11y básica + tema/`cor_primaria` + erros seguros  

---

*Atualize só com gaps reais frente ao código — não reabrir o que já está feito.*
