<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'salao_id', 'nome', 'email', 'telefone', 'cpf',
        'data_nascimento', 'aniversario_enviado_em', 'endereco', 'observacoes', 'alergias',
        'total_visitas', 'total_gasto', 'pontos_fidelidade', 'ativo',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'aniversario_enviado_em' => 'date',
        'ativo' => 'boolean',
        'total_gasto' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function pontosFidelidade()
    {
        return $this->hasMany(FidelidadePonto::class);
    }

    public function comandas()
    {
        return $this->hasMany(Comanda::class);
    }

    public function getAniversarioHojeAttribute(): bool
    {
        if (!$this->data_nascimento) return false;
        return $this->data_nascimento->format('d-m') === now()->format('d-m');
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento ? $this->data_nascimento->age : null;
    }

    public function getTotalFaltasAttribute(): int
    {
        return $this->agendamentos()
            ->where('status', \App\Enums\AgendamentoStatus::NaoCompareceu->value)
            ->count();
    }

    public function getEhRiscoNoShowAttribute(): bool
    {
        return $this->total_faltas >= (int) config('manicure.no_show.limite_alerta', 2);
    }
}
