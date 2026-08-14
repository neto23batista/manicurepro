<?php

namespace App\Models;

use App\Models\Concerns\HasAvatar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manicure extends Model
{
    use HasAvatar, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'salao_id', 'nome', 'email', 'telefone',
        'foto', 'bio', 'especialidades', 'comissao', 'ativo',
    ];

    protected $casts = [
        'especialidades' => 'array',
        'ativo'          => 'boolean',
        'comissao'       => 'decimal:2',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return HasMany<Agendamento, $this> */
    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    /** @return HasMany<ComissaoPagamento, $this> */
    public function comissaoPagamentos(): HasMany
    {
        return $this->hasMany(ComissaoPagamento::class);
    }

    /** @return HasMany<ComissaoAjuste, $this> */
    public function comissaoAjustes(): HasMany
    {
        return $this->hasMany(ComissaoAjuste::class);
    }

    /** @return HasMany<DisponibilidadeManicure, $this> */
    public function disponibilidades(): HasMany
    {
        return $this->hasMany(DisponibilidadeManicure::class)->orderBy('dia_semana');
    }

    /** @return HasMany<FolgaManicure, $this> */
    public function folgas(): HasMany
    {
        return $this->hasMany(FolgaManicure::class);
    }

    /** @return HasMany<Avaliacao, $this> */
    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function getNotaMediaAttribute(): float
    {
        if (array_key_exists('nota_media_calc', $this->attributes)) {
            return round((float) $this->attributes['nota_media_calc'], 1);
        }

        return round($this->avaliacoes()->publicadas()->avg('nota') ?? 0, 1);
    }

    /** @return HasMany<Agendamento, $this> */
    public function agendamentosHoje(): HasMany
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
