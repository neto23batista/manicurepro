<?php

namespace App\Services;

/**
 * TOTP (RFC 6238) + códigos de recuperação — implementação própria,
 * sem dependências externas. Algoritmo SHA1, 6 dígitos, período de 30s
 * (compatível com Google Authenticator, Authy, Microsoft Authenticator, etc.).
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
        if (! preg_match('/^\d{6}$/', $code)) {
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
        $binary = pack('N*', 0).pack('N*', $timeSlice); // contador big-endian de 8 bytes
        $hash = hash_hmac('sha1', $binary, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $part = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($part % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function otpauthUri(string $secret, string $label, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$label)
            .'?secret='.$secret
            .'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1&digits=6&period=30';
    }

    /**
     * Códigos de recuperação de uso único (ex.: ABCD-EFGH).
     * O plaintext só deve ser exibido uma vez — persista apenas o hash.
     */
    public function generateRecoveryCodes(int $count = 8, int $segmentLength = 4): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->randomSegment($segmentLength).'-'.$this->randomSegment($segmentLength);
        }

        return $codes;
    }

    /**
     * @param  list<string>  $plainCodes
     * @return list<string>
     */
    public function hashRecoveryCodes(array $plainCodes): array
    {
        $hashed = [];
        foreach ($plainCodes as $code) {
            $hashed[] = password_hash($this->normalizeRecoveryCode($code), PASSWORD_DEFAULT);
        }

        return $hashed;
    }

    /**
     * Consome um código de recuperação.
     * Retorna a lista restante de hashes se válido, ou null se não bater.
     * Hashes podem vir do banco como JSON misto — valida tipo em runtime.
     *
     * @param  array<int, mixed>  $hashedCodes
     * @return list<string>|null
     */
    public function consumeRecoveryCode(array $hashedCodes, string $plainCode): ?array
    {
        $plainCode = $this->normalizeRecoveryCode($plainCode);
        if ($plainCode === '' || $hashedCodes === []) {
            return null;
        }

        foreach ($hashedCodes as $i => $hash) {
            if (! is_string($hash) || $hash === '') {
                continue;
            }
            if (password_verify($plainCode, $hash)) {
                unset($hashedCodes[$i]);

                /** @var list<string> $remaining */
                $remaining = array_values(array_filter(
                    $hashedCodes,
                    static fn (mixed $value): bool => is_string($value) && $value !== ''
                ));

                return $remaining;
            }
        }

        return null;
    }

    public function normalizeRecoveryCode(string $code): string
    {
        $alnum = preg_replace('/[^A-Za-z2-7]/', '', strtoupper($code)) ?? '';
        if (strlen($alnum) === 8) {
            return substr($alnum, 0, 4).'-'.substr($alnum, 4, 4);
        }

        return strtoupper(preg_replace('/\s+/', '', $code) ?? '');
    }

    private function randomSegment(int $length): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $this->alphabet[random_int(0, 31)];
        }

        return $out;
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
                $out .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $out;
    }
}
