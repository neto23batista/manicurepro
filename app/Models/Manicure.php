<?php

namespace App\Models;

use App\Models\Concerns\HasAvatar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manicure extends Model
{
    use HasFactory, HasAvatar, SoftDeletes;

    protected $fillable = [
        'user_id', 'salao_id', 'nome', 'email', 'telefone',
        'foto', 'bio', 'especialidades', 'comissao', 'ativo',
    ];

    protected $casts = [
        'especialidades' => 'array',
        'ativo' => 'boolean',
        'comissao' => 'decimal:2',
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

    public function disponibilidades()
    {
        return $this->hasMany(DisponibilidadeManicure::class)->orderBy('dia_semana');
    }

    public function folgas()
    {
        return $this->hasMany(FolgaManicure::class);
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function getNotaMediaAttribute(): float
    {
        if (array_key_exists('nota_media_calc', $this->attributes)) {
            return round((float) $this->attributes['nota_media_calc'], 1);
        }
        return round($this->avaliacoes()->avg('nota') ?? 0, 1);
    }

    public function agendamentosHoje()
    {
        return $this->agendamentos()
            ->whereDate('data_hora_inicio', today())
            ->orderBy('data_hora_inicio');
    }

    public function temDisponibilidade(int $diaSemana): bool
    {
        return $this->disponibilidades()
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->exists();
    }

    public function estaEmFolga(Carbon $data): bool
    {
        return $this->folgas()
            ->whereDate('data', $data->toDateString())
            ->exists();
    }

    protected function avatarColumn(): string
    {
        return 'foto';
    }

    protected function avatarSourceName(): ?string
    {
        return $this->nome;
    }
}
