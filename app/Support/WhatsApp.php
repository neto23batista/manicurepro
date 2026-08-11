<?php

namespace App\Support;

class WhatsApp
{
    /**
     * Normaliza um telefone BR para o formato da Cloud API
     * (apenas dígitos, com DDI). Retorna null se inválido.
     *
     * Aceita variações comuns: máscara, +, 00, zero de tronco (0XX).
     */
    public static function normalizarTelefone(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // Prefixo internacional 00…
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $ddi = (string) config('manicure.whatsapp.ddi_padrao', '55');

        // 55011… → 5511… (zero de tronco após o DDI)
        if (str_starts_with($digits, $ddi.'0') && strlen($digits) >= strlen($ddi) + 11) {
            $digits = $ddi.substr($digits, strlen($ddi) + 1);
        }

        // 011988887777 → 11988887777 (tronco local sem DDI)
        if (! str_starts_with($digits, $ddi)
            && str_starts_with($digits, '0')
            && strlen($digits) >= 11
            && strlen($digits) <= 12
        ) {
            $digits = substr($digits, 1);
        }

        // Número nacional (DDD + 8/9 dígitos) → prefixa DDI
        if (! str_starts_with($digits, $ddi) && (strlen($digits) === 10 || strlen($digits) === 11)) {
            $digits = $ddi.$digits;
        }

        if (! str_starts_with($digits, $ddi)) {
            return null;
        }

        $nacional = substr($digits, strlen($ddi));

        // BR: DDD (2) + local (8 fixo / 9 móvel)
        if (strlen($nacional) < 10 || strlen($nacional) > 11) {
            return null;
        }

        // DDD não começa com 0
        if ($nacional[0] === '0') {
            return null;
        }

        return $digits;
    }
}
