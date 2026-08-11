<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fornecedor extends Model
{
    protected $table = 'fornecedores';

    protected $fillable = [
        'salao_id', 'nome', 'contato', 'telefone', 'email',
        'documento', 'observacoes', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return HasMany<Produto, $this> */
    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }
}
