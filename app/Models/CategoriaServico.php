<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaServico extends Model
{
    use HasFactory;

    protected $table = 'categorias_servico';

    protected $fillable = ['salao_id', 'nome', 'descricao', 'cor', 'icone', 'ordem', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function servicos()
    {
        return $this->hasMany(Servico::class, 'categoria_id')->where('ativo', true);
    }
}
