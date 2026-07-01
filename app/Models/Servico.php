<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    use HasFactory;

    protected $fillable = [
        'salao_id', 'categoria_id', 'nome', 'descricao', 'preco',
        'duracao', 'comissao_percentual', 'combo', 'disponivel_online', 'ativo',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'comissao_percentual' => 'decimal:2',
        'combo' => 'boolean',
        'disponivel_online' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaServico::class, 'categoria_id');
    }

    public function agendamentos()
    {
        return $this->belongsToMany(Agendamento::class, 'agendamento_servicos')
            ->withPivot('preco', 'duracao')
            ->withTimestamps();
    }

    public function getDuracaoFormatadaAttribute(): string
    {
        $h = intdiv($this->duracao, 60);
        $m = $this->duracao % 60;
        if ($h > 0) return "{$h}h" . ($m > 0 ? " {$m}min" : '');
        return "{$m}min";
    }

    public function getPrecoFormatadoAttribute(): string
    {
        return 'R$ ' . number_format($this->preco, 2, ',', '.');
    }
}
