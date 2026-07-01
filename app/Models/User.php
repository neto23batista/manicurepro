<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Models\Concerns\HasAvatar;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasAvatar, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'salao_id', 'avatar', 'phone', 'ativo',
        'two_factor_secret', 'two_factor_confirmed_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'ativo' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    public function roleEnum(): ?UserRole
    {
        return $this->role ? UserRole::tryFrom($this->role) : null;
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

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function manicure()
    {
        return $this->hasOne(Manicure::class);
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class);
    }

    public function notificacoes()
    {
        return $this->hasMany(Notificacao::class)->orderByDesc('created_at');
    }

    public function notificacoesNaoLidas()
    {
        return $this->hasMany(Notificacao::class)->where('lida', false);
    }

    protected function avatarSourceName(): ?string
    {
        return $this->name;
    }

    public function routeNotificationForWhatsApp(): ?string
    {
        return \App\Support\WhatsApp::normalizarTelefone($this->phone);
    }
}
