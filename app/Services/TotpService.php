<?php

namespace App\Services;

/**
 * TOTP (RFC 6238) — implementação própria, sem dependências externas.
 * Algoritmo SHA1, 6 dígitos, período de 30s (compatível com Google
 * Authenticator, Authy, Microsoft Authenticator, etc.).
 */
class TotpService
{
    private string $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $this->alphabet[random_int(0, 31)];
        }
        return $secret;
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $slice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->codeAt($secret, $slice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public function codeAt(string $secret, int $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $binary = pack('N*', 0) . pack('N*', $timeSlice); // contador big-endian de 8 bytes
        $hash = hash_hmac('sha1', $binary, $key, true);

        $offset = ord($hash[19]) & 0x0f;
        $part = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        );

        return str_pad((string) ($part % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function otpauthUri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $label)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    private function base32Decode(string $b32): string
    {
        $b32 = rtrim(strtoupper($b32), '=');
        $buffer = 0;
        $bitsLeft = 0;
        $out = '';

        foreach (str_split($b32) as $char) {
            $val = strpos($this->alphabet, $char);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $out .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }

        return $out;
    }
}
