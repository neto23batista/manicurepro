<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ConfiguracaoSalao;
use App\Models\User;

/**
 * Permissões extras leves por role (JSON em configuracoes_salao).
 * Sem Spatie: defaults das 5 roles continuam intactos até haver grants.
 *
 * Estrutura armazenada:
 * {
 *   "atendente": { "grant": ["financeiro.view"], "revoke": [] }
 * }
 *
 * - grant: habilidades além do default da role (somente role atendente)
 * - revoke: remove grants previamente dados (não altera defaults embutidos nas Policies/rotas)
 */
class PermissionService
{
    /** Roles que podem receber grants extras (painel dono sensível). */
    public const GRANTABLE_ROLES = ['atendente'];

    /** @return array<string, string> chave => rótulo */
    public static function catalog(): array
    {
        return config('manicure.permissions.catalog', []);
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::catalog());
    }

    public function grantsFor(User $user): array
    {
        $role = $user->roleEnum();
        if (! $role || ! $user->salao_id) {
            return [];
        }

        $config = ConfiguracaoSalao::paraSalao((int) $user->salao_id);
        if (! $config) {
            return [];
        }

        return $this->normalizeRoleBucket($config->role_permissions, $role->value)['grant'];
    }

    public function hasGrant(User $user, string $permission): bool
    {
        if (! in_array($permission, self::keys(), true)) {
            return false;
        }

        // Admin/dono já cobrem operação via RoleMiddleware; grant é só para atendente.
        if ($user->isSuperAdmin() || $user->isDono()) {
            return true;
        }

        $role = $user->roleEnum()?->value;
        if (! $role || ! in_array($role, self::GRANTABLE_ROLES, true)) {
            return false;
        }

        return in_array($permission, $this->grantsFor($user), true);
    }

    /**
     * @param  array<string, mixed>|null  $raw
     * @return array{grant: list<string>, revoke: list<string>}
     */
    public function normalizeRoleBucket(?array $raw, string $role): array
    {
        $bucket = is_array($raw[$role] ?? null) ? $raw[$role] : [];
        $grant = $this->filterKeys($bucket['grant'] ?? $bucket);
        $revoke = $this->filterKeys($bucket['revoke'] ?? []);

        // Revoke só remove de grant (defaults de role não mudam).
        $grant = array_values(array_diff($grant, $revoke));

        return ['grant' => $grant, 'revoke' => $revoke];
    }

    /**
     * Normaliza o payload do formulário para persistência.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, array{grant: list<string>, revoke: list<string>}>
     */
    public function sanitizePayload(array $input): array
    {
        $out = [];
        foreach (UserRole::cases() as $role) {
            if (! in_array($role->value, self::GRANTABLE_ROLES, true)) {
                continue;
            }

            $bucket = is_array($input[$role->value] ?? null) ? $input[$role->value] : [];
            $grant = $this->filterKeys($bucket['grant'] ?? []);
            $revoke = $this->filterKeys($bucket['revoke'] ?? []);
            $grant = array_values(array_diff($grant, $revoke));

            if ($grant === [] && $revoke === []) {
                continue;
            }

            $out[$role->value] = [
                'grant'  => $grant,
                'revoke' => $revoke,
            ];
        }

        return $out;
    }

    /** @param  mixed  $keys */
    private function filterKeys(mixed $keys): array
    {
        if (! is_array($keys)) {
            return [];
        }

        $allowed = self::keys();

        return array_values(array_unique(array_filter(
            $keys,
            fn ($k) => is_string($k) && in_array($k, $allowed, true)
        )));
    }
}
