<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasAvatar;
use App\Support\WhatsApp;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasAvatar, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'salao_id', 'avatar', 'phone', 'ativo',
        'two_factor_secret', 'two_factor_confirmed_at', 'two_factor_recovery_codes',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $casts = [
        'email_verified_at'         => 'datetime',
        'password'                  => 'hashed',
        'ativo'                     => 'boolean',
        'two_factor_secret'         => 'encrypted',
        'two_factor_confirmed_at'   => 'datetime',
        'two_factor_recovery_codes' => 'array',
    ];

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    public function roleEnum(): ?UserRole
    {
        return UserRole::tryFrom((string) $this->role);
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->roleEnum() === $role;
    }

    public function isAdmin(): bool
    {
        $role = $this->roleEnum();

        return $role === UserRole::Admin || $role === UserRole::Dono;
    }

    public function isDono(): bool
    {
        return $this->hasRole(UserRole::Dono);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isManicure(): bool
    {
        return $this->hasRole(UserRole::Manicure);
    }

    public function isCliente(): bool
    {
        return $this->hasRole(UserRole::Cliente);
    }

    public function isAtendente(): bool
    {
        return $this->hasRole(UserRole::Atendente);
    }

    /**
     * Grant extra (JSON em configuracoes_salao) além do default da role.
     * Sem grants configurados, retorna false para roles que não são dono/admin.
     */
    public function hasExtraPermission(string $permission): bool
    {
        return app(\App\Services\PermissionService::class)->hasGrant($this, $permission);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return HasOne<Manicure, $this> */
    public function manicure(): HasOne
    {
        return $this->hasOne(Manicure::class);
    }

    /** @return HasOne<Cliente, $this> */
    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class);
    }

    /** @return HasMany<Notificacao, $this> */
    public function notificacoes(): HasMany
    {
        return $this->hasMany(Notificacao::class)->orderByDesc('created_at');
    }

    /** @return HasMany<Notificacao, $this> */
    public function notificacoesNaoLidas(): HasMany
    {
        return $this->hasMany(Notificacao::class)->where('lida', false);
    }

    /** @return HasMany<PushSubscription, $this> */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    protected function avatarSourceName(): ?string
    {
        return $this->name;
    }

    public function routeNotificationForWhatsApp(): ?string
    {
        return WhatsApp::normalizarTelefone($this->phone);
    }
}
