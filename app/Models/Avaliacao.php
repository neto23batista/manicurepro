<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    protected $fillable = [
        'agendamento_id', 'cliente_id', 'manicure_id', 'salao_id',
        'nota', 'comentario', 'resposta', 'respondido_em', 'publicar',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
        'publicar' => 'boolean',
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function manicure()
    {
        return $this->belongsTo(Manicure::class);
    }

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function getEstrelasAttribute(): string
    {
        return str_repeat('★', $this->nota) . str_repeat('☆', 5 - $this->nota);
    }
}
