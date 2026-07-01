<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FidelidadePonto extends Model
{
    protected $table = 'fidelidade_pontos';

    protected $fillable = [
        'cliente_id', 'salao_id', 'agendamento_id',
        'pontos', 'tipo', 'descricao',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class);
    }
}
