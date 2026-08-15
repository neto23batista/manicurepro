<?php

/**
 * Configurações de domínio do Fernanda Silva Nails.
 * Acesse via `config('manicure.agenda.intervalo_default')` etc.
 */
return [

    'agenda' => [
        'intervalo_default' => env('MANICURE_INTERVALO_DEFAULT', 30),    // minutos
        'antecedencia_min'  => env('MANICURE_ANTECEDENCIA_MIN', 1),       // dias
        'antecedencia_max'  => env('MANICURE_ANTECEDENCIA_MAX', 30),      // dias
        'lembrete_horas'    => env('MANICURE_LEMBRETE_HORAS', 24),
        'hold_minutos'      => env('MANICURE_HOLD_MINUTOS', 10),  // reserva temporária de slot
    ],

    'fidelidade' => [
        'pontos_por_real'      => env('MANICURE_PONTOS_POR_REAL', 1),
        'pontos_para_desconto' => env('MANICURE_PONTOS_DESCONTO', 100),
        'valor_desconto'       => env('MANICURE_VALOR_DESCONTO', 10.00),
        /*
         * Níveis Bronze→VIP (pontos_min = total de pontos já ganhos, tipo=ganho).
         * multiplicador aplica-se ao crédito de pontos no atendimento.
         * Edite aqui ou via env FIDELIDADE_NIVEIS_JSON (array JSON).
         */
        'niveis' => json_decode((string) env('FIDELIDADE_NIVEIS_JSON', ''), true) ?: [
            ['chave' => 'bronze', 'nome' => 'Bronze', 'pontos_min' => 0,   'multiplicador' => 1.0],
            ['chave' => 'prata',  'nome' => 'Prata',  'pontos_min' => 100, 'multiplicador' => 1.1],
            ['chave' => 'ouro',   'nome' => 'Ouro',   'pontos_min' => 300, 'multiplicador' => 1.25],
            ['chave' => 'vip',    'nome' => 'VIP',    'pontos_min' => 600, 'multiplicador' => 1.5],
        ],
        // null/0 = pontos não expiram. Ex.: 365 = expiram 1 ano após o ganho.
        'expiracao_dias' => env('FIDELIDADE_EXPIRACAO_DIAS') !== null && env('FIDELIDADE_EXPIRACAO_DIAS') !== ''
            ? (int) env('FIDELIDADE_EXPIRACAO_DIAS')
            : null,
    ],

    /*
     * CRM — segmentação de clientes (não confundir com fidelidade/pontos).
     * Usado em ClienteSegmentacao + filtros em dono.clientes.
     */
    'crm' => [
        'novo_dias'              => (int) env('CRM_NOVO_DIAS', 30),
        'recorrente_min_visitas' => (int) env('CRM_RECORRENTE_MIN_VISITAS', 3),
        'inativo_dias'           => (int) env('CRM_INATIVO_DIAS', 60),
        // Janela de “esfriando”: última visita entre risco_churn_dias e inativo_dias.
        'risco_churn_dias'    => (int) env('CRM_RISCO_CHURN_DIAS', 40),
        'vip_gasto_minimo'    => (float) env('CRM_VIP_GASTO_MINIMO', 500),
        'vip_visitas_minimas' => (int) env('CRM_VIP_VISITAS_MINIMAS', 8),
        'reativacao'          => [
            'cupom_tipo'          => env('CRM_REATIVACAO_CUPOM_TIPO', 'percentual'), // percentual | fixo
            'cupom_valor'         => (float) env('CRM_REATIVACAO_CUPOM_VALOR', 15),
            'cupom_validade_dias' => (int) env('CRM_REATIVACAO_CUPOM_VALIDADE', 30),
        ],
    ],

    /*
     * Programa de indicação. Quando enabled, o indicador recebe recompensa
     * na primeira visita concluída do indicado (pontos ou cupom).
     */
    'indicacao' => [
        'enabled'             => env('INDICACAO_ENABLED', true),
        'recompensa'          => env('INDICACAO_RECOMPENSA', 'pontos'), // pontos | cupom
        'pontos'              => env('INDICACAO_PONTOS', 50),
        'cupom_valor'         => env('INDICACAO_CUPOM_VALOR', 20),
        'cupom_validade_dias' => env('INDICACAO_CUPOM_VALIDADE', 30),
    ],

    'no_show' => [
        // A partir de quantas faltas o cliente passa a exibir alerta de risco no painel.
        'limite_alerta' => env('MANICURE_NO_SHOW_ALERTA', 2),
    ],

    /*
     * Estoque de produtos. `minimo_padrao` é o limiar sugerido no cadastro
     * (alerta visual no index/dashboard quando estoque_atual <= estoque_minimo).
     * `notificar_zerado` dispara e-mail/database ao dono quando uma venda zera o estoque.
     */
    'estoque' => [
        'minimo_padrao'    => env('ESTOQUE_MINIMO_PADRAO', 1),
        'notificar_zerado' => env('ESTOQUE_NOTIFICAR_ZERADO', true),
        /** Dias sem movimentação para considerar produto “parado” no relatório. */
        'dias_parado' => env('ESTOQUE_DIAS_PARADO', 60),
    ],

    /*
     * Marketing de retenção (comandos + listener pós-atendimento).
     * Gate global: com enabled=false, reativar/sugerir-retorno/pedir avaliação
     * não disparam. Cooldown evita spam; cadência controla sugestão de retorno.
     */
    'marketing' => [
        'enabled'  => env('MARKETING_ENABLED', true),
        'reativar' => [
            'cooldown_dias' => (int) env('MARKETING_REATIVAR_COOLDOWN', 30),
            'com_cupom'     => env('MARKETING_REATIVAR_CUPOM', true),
        ],
        'retorno' => [
            // Dias após a última visita concluída para sugerir retorno.
            'cadencia_dias' => (int) env('MARKETING_RETORNO_CADENCIA', 28),
            // Janela (dias) em torno da cadência para capturar o cliente.
            'janela_dias'   => (int) env('MARKETING_RETORNO_JANELA', 3),
            'cooldown_dias' => (int) env('MARKETING_RETORNO_COOLDOWN', 25),
        ],
    ],

    /*
     * Felicitação automática de aniversário. Roda no comando agendado
     * `manicure:enviar-aniversarios` (diário). Opcionalmente gera um cupom de
     * desconto de presente, válido por `cupom_validade_dias` dias.
     */
    'aniversario' => [
        'enabled'             => env('ANIVERSARIO_ENABLED', true),
        'cupom_presente'      => env('ANIVERSARIO_CUPOM', true),
        'cupom_tipo'          => env('ANIVERSARIO_CUPOM_TIPO', 'percentual'), // percentual | fixo
        'cupom_valor'         => env('ANIVERSARIO_CUPOM_VALOR', 15),          // 15% ou R$ 15,00
        'cupom_validade_dias' => env('ANIVERSARIO_CUPOM_VALIDADE', 30),
    ],

    /*
     * Pagamento online via Pix (Mercado Pago).
     * Ative preenchendo MP_* e as flags SINAL_HABILITADO / PAGAMENTO_TOTAL_HABILITADO no .env.
     * Sem token a cobrança é ignorada e o agendamento segue o fluxo normal.
     */
    'pagamento' => [
        'mercadopago' => [
            'enabled'        => env('MP_ENABLED', false),
            'access_token'   => env('MP_ACCESS_TOKEN'),
            'webhook_secret' => env('MP_WEBHOOK_SECRET'),
        ],
        'sinal' => [
            'habilitado' => env('SINAL_HABILITADO', false),
            'tipo'       => env('SINAL_TIPO', 'percentual'), // percentual | fixo
            'valor'      => env('SINAL_VALOR', 30),           // 30% ou R$ 30,00
        ],
        'total' => [
            // Cobra o líquido do agendamento (ou o restante se o sinal já foi pago).
            'habilitado' => env('PAGAMENTO_TOTAL_HABILITADO', false),
        ],
        'gorjeta' => [
            // Pix de gorjeta pós-atendimento (cliente). Default off.
            'habilitado' => env('GORJETA_ONLINE_HABILITADO', false),
        ],
    ],

    'cache_ttl' => [
        'configuracao_salao'  => env('MANICURE_CACHE_CONFIG_TTL', 3600),   // 1h
        'notificacoes_topbar' => env('MANICURE_CACHE_NOTIF_TTL', 60),      // 60s
        'slots_disponiveis'   => env('MANICURE_CACHE_SLOTS_TTL', 60),      // 60s
    ],

    'ui_avatars' => [
        'background' => env('MANICURE_AVATAR_BG', 'e91e8c'),
        'color'      => env('MANICURE_AVATAR_FG', 'fff'),
        'size'       => env('MANICURE_AVATAR_SIZE', 128),
    ],

    'tema' => [
        'cor_primaria' => env('MANICURE_COR_PRIMARIA', '#e91e8c'),
    ],

    /*
     * Redes sociais exibidas no rodapé público.
     * Deixe vazio para ocultar o ícone correspondente.
     */
    'social' => [
        'instagram' => env('MANICURE_INSTAGRAM'), // URL completa ou @usuario
        'facebook'  => env('MANICURE_FACEBOOK'),  // URL completa
        'tiktok'    => env('MANICURE_TIKTOK'),    // URL completa ou @usuario
        'whatsapp'  => env('MANICURE_WHATSAPP'),  // telefone com DDI ou URL wa.me
    ],

    /*
     * Segurança — Content-Security-Policy.
     * `enabled` liga/desliga o cabeçalho CSP. `report_only` envia a política como
     * Content-Security-Policy-Report-Only (não bloqueia, só reporta) — útil para
     * validar em produção sem risco antes de passar a bloquear de fato.
     */
    'security' => [
        'csp_enabled'     => env('CSP_ENABLED', true),
        'csp_report_only' => env('CSP_REPORT_ONLY', false),
    ],

    /*
     * WhatsApp via Meta (Cloud API).
     * Ative preenchendo WHATSAPP_* no .env. Sem token, o canal é ignorado
     * silenciosamente (as notificações continuam indo por e-mail/database).
     *
     * Observação: mensagens iniciadas pelo negócio (lembretes) exigem
     * TEMPLATES aprovados na Meta. Configure os nomes em `templates`.
     */
    'whatsapp' => [
        'enabled'         => env('WHATSAPP_ENABLED', false),
        'token'           => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version'     => env('WHATSAPP_API_VERSION', 'v21.0'),
        'ddi_padrao'      => env('WHATSAPP_DDI_PADRAO', '55'), // Brasil
        'templates'       => [
            'lembrete'   => env('WHATSAPP_TEMPLATE_LEMBRETE'),
            'confirmado' => env('WHATSAPP_TEMPLATE_CONFIRMADO'),
        ],
    ],

    /*
     * Fiscal / NF-e — STUB apenas (NÃO emite na SEFAZ).
     * Quando enabled=true, o dono pode criar rascunhos locais vinculados a
     * agendamento/comanda. Não há certificado, autorização nem webservice.
     */
    'fiscal' => [
        'enabled' => env('FISCAL_ENABLED', false),
    ],

    /*
     * Permissões extras por role (JSON em configuracoes_salao.role_permissions).
     * Sem Spatie: defaults das 5 roles NÃO mudam até haver grant explícito.
     * grant = além do default; revoke = remove do grant (não altera Policies base).
     */
    'permissions' => [
        'catalog' => [
            'financeiro.view'      => 'Ver financeiro e comissões',
            'financeiro.caixa'     => 'Operar caixa diário',
            'financeiro.despesas'  => 'Gerenciar despesas',
            'vales.manage'         => 'Gerenciar vale-presente',
            'config.manage'        => 'Editar configurações do salão',
            'auditoria.view'       => 'Ver logs de auditoria',
            'notas_fiscais.manage' => 'Notas fiscais (stub)',
        ],
    ],

    /*
     * Web Push (PWA). Requer minishlink/web-push + VAPID.
     * WEBPUSH_SUBSCRIBE_UI omitido → auto (liga se VAPID estiver preenchido).
     * WEBPUSH_SUBSCRIBE_UI=false → força UI escondida; =true → força permitir.
     */
    'webpush' => [
        'subscribe_ui' => env('WEBPUSH_SUBSCRIBE_UI') !== null
            ? filter_var(env('WEBPUSH_SUBSCRIBE_UI'), FILTER_VALIDATE_BOOLEAN)
            : (filled(env('VAPID_PUBLIC_KEY')) && filled(env('VAPID_PRIVATE_KEY'))),
        'vapid' => [
            'subject'     => env('VAPID_SUBJECT', 'mailto:noreply@manicurepro.com.br'),
            'public_key'  => env('VAPID_PUBLIC_KEY'),
            'private_key' => env('VAPID_PRIVATE_KEY'),
        ],
    ],

];
