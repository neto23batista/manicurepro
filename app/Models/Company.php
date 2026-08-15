<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'documento',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /** @return HasMany<Salao, $this> */
    public function saloes(): HasMany
    {
        return $this->hasMany(Salao::class);
    }
}
