<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pacote extends Model
{
    use HasFactory;

    protected $table = 'pacotes';

    protected $fillable = [
        'salao_id', 'nome', 'sessoes', 'validade_dias', 'preco', 'ativo',
    ];

    protected $casts = [
        'sessoes'       => 'integer',
        'validade_dias' => 'integer',
        'preco'         => 'decimal:2',
        'ativo'         => 'boolean',
    ];

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return HasMany<ClientePacote, $this> */
    public function clientePacotes(): HasMany
    {
        return $this->hasMany(ClientePacote::class);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
