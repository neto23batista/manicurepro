<?php

namespace App\Support;

class WhatsApp
{
    /**
     * Normaliza um telefone para o formato esperado pela Cloud API
     * (apenas dígitos, com DDI). Retorna null se inválido.
     */
    public static function normalizarTelefone(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw);

        if (strlen($digits) < 10) {
            return null;
        }

        $ddi = (string) config('manicure.whatsapp.ddi_padrao', '55');

        // Se for um número local (10–11 dígitos), prefixa o DDI.
        if (!str_starts_with($digits, $ddi) && strlen($digits) <= 11) {
            $digits = $ddi . $digits;
        }

        return $digits;
    }
}
