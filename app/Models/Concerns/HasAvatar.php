<?php

namespace App\Models\Concerns;

/**
 * Reutilizável por models que possuem uma coluna de imagem (avatar/foto)
 * armazenada no disco `public` com fallback para ui-avatars.com.
 *
 * Espera que o model exponha:
 *   - $this->avatarColumn() — nome da coluna (default: 'avatar')
 *   - $this->avatarSourceName() — string para gerar iniciais (ex: nome do user)
 *
 * Disponibiliza o accessor:
 *   - $model->avatar_url      (User)
 *   - $model->foto_url        (legacy alias para Manicure)
 */
trait HasAvatar
{
    public function getAvatarUrlAttribute(): string
    {
        return $this->resolveAvatarUrl();
    }

    public function getFotoUrlAttribute(): string
    {
        return $this->resolveAvatarUrl();
    }

    protected function resolveAvatarUrl(): string
    {
        $column = $this->avatarColumn();
        $path = $this->{$column} ?? null;

        if ($path && file_exists(public_path('storage/'.$path))) {
            return asset('storage/'.$path);
        }

        $initials = urlencode(substr($this->avatarSourceName() ?? '?', 0, 2));
        $bg = config('manicure.ui_avatars.background', 'e91e8c');
        $fg = config('manicure.ui_avatars.color', 'fff');
        $size = config('manicure.ui_avatars.size', 128);

        return "https://ui-avatars.com/api/?name={$initials}&background={$bg}&color={$fg}&size={$size}";
    }

    protected function avatarColumn(): string
    {
        return 'avatar';
    }

    protected function avatarSourceName(): ?string
    {
        return $this->name ?? $this->nome ?? null;
    }
}
