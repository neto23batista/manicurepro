<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Notificacao extends Model
{
    protected $table = 'notificacoes';

    protected $fillable = [
        'user_id', 'titulo', 'mensagem', 'tipo', 'icone',
        'lida', 'dados', 'url', 'lida_em',
    ];

    protected $casts = [
        'lida'    => 'boolean',
        'dados'   => 'array',
        'lida_em' => 'datetime',
    ];

    /**
     * Invalida o cache do topbar quando uma notificação é criada, atualizada ou removida.
     */
    protected static function booted(): void
    {
        $invalidate = fn (Notificacao $n) => Cache::forget("user:{$n->user_id}:notif_topbar");

        static::created($invalidate);
        static::updated($invalidate);
        static::deleted($invalidate);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marcarLida(): void
    {
        $this->update(['lida' => true, 'lida_em' => now()]);
    }
}
