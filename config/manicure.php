<?php

/**
 * Configurações de domínio do Fernanda Silva Nails.
 * Acesse via `config('manicure.agenda.intervalo_default')` etc.
 */
return [

    'agenda' => [
        'intervalo_default'   => env('MANICURE_INTERVALO_DEFAULT', 30),    // minutos
        'antecedencia_min'    => env('MANICURE_ANTECEDENCIA_MIN', 1),       // dias
        'antecedencia_max'    => env('MANICURE_ANTECEDENCIA_MAX', 30),      // dias
        'lembrete_horas'      => env('MANICURE_LEMBRETE_HORAS', 24),
        'hold_minutos'        => env('MANICURE_HOLD_MINUTOS', 10),  // reserva temporária de slot
    ],

    'fidelidade' => [
        'pontos_por_real'      => env('MANICURE_PONTOS_POR_REAL', 1),
        'pontos_para_desconto' => env('MANICURE_PONTOS_DESCONTO', 100),
        'valor_desconto'       => env('MANICURE_VALOR_DESCONTO', 10.00),
    ],

    'no_show' => [
        // A partir de quantas faltas o cliente passa a exibir alerta de risco no painel.
        'limite_alerta' => env('MANICURE_NO_SHOW_ALERTA', 2),
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
     * Pagamento online (sinal antecipado via Pix — Mercado Pago).
     * Ative preenchendo MP_* e SINAL_HABILITADO no .env. Sem token a cobrança
     * é ignorada e o agendamento segue o fluxo normal sem sinal.
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
    ],

    'cache_ttl' => [
        'configuracao_salao'   => env('MANICURE_CACHE_CONFIG_TTL', 3600),   // 1h
        'notificacoes_topbar'  => env('MANICURE_CACHE_NOTIF_TTL', 60),      // 60s
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
        'templates' => [
            'lembrete'   => env('WHATSAPP_TEMPLATE_LEMBRETE'),
            'confirmado' => env('WHATSAPP_TEMPLATE_CONFIRMADO'),
        ],
    ],

];
