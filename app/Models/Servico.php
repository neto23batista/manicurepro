<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Servico extends Model
{
    use HasFactory;

    protected $fillable = [
        'salao_id', 'categoria_id', 'nome', 'descricao', 'imagem', 'preco',
        'custo_estimado', 'duracao', 'comissao_percentual', 'comissao_fixo',
        'combo', 'disponivel_online', 'ativo',
    ];

    protected $casts = [
        'preco'               => 'decimal:2',
        'custo_estimado'      => 'decimal:2',
        'comissao_percentual' => 'decimal:2',
        'comissao_fixo'      => 'decimal:2',
        'combo'               => 'boolean',
        'disponivel_online'   => 'boolean',
        'ativo'               => 'boolean',
    ];

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return BelongsTo<CategoriaServico, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaServico::class, 'categoria_id');
    }

    /** @return BelongsToMany<Agendamento, $this> */
    public function agendamentos(): BelongsToMany
    {
        return $this->belongsToMany(Agendamento::class, 'agendamento_servicos')
            ->withPivot('preco', 'duracao', 'servico_variacao_id')
            ->withTimestamps();
    }

    /** @return HasMany<ServicoVariacao, $this> */
    public function variacoes(): HasMany
    {
        return $this->hasMany(ServicoVariacao::class)->orderBy('ordem')->orderBy('id');
    }

    /** @return HasMany<ServicoVariacao, $this> */
    public function variacoesAtivas(): HasMany
    {
        return $this->variacoes()->where('ativo', true);
    }

    public function getImagemUrlAttribute(): ?string
    {
        if (! $this->imagem) {
            return null;
        }

        if (str_starts_with($this->imagem, 'http://') || str_starts_with($this->imagem, 'https://')) {
            return $this->imagem;
        }

        return Storage::disk('public')->url($this->imagem);
    }

    public function getDuracaoFormatadaAttribute(): string
    {
        $h = intdiv($this->duracao, 60);
        $m = $this->duracao % 60;
        if ($h > 0) {
            return "{$h}h".($m > 0 ? " {$m}min" : '');
        }

        return "{$m}min";
    }

    public function getPrecoFormatadoAttribute(): string
    {
        return 'R$ '.number_format((float) $this->preco, 2, ',', '.');
    }

    public function getCustoEstimadoFormatadoAttribute(): ?string
    {
        if ($this->custo_estimado === null) {
            return null;
        }

        return 'R$ '.number_format((float) $this->custo_estimado, 2, ',', '.');
    }

    /**
     * Preço de exibição: base, ou faixa quando há variações ativas.
     */
    public function getPrecoExibicaoAttribute(): string
    {
        $vars = $this->relationLoaded('variacoesAtivas')
            ? $this->variacoesAtivas
            : $this->variacoesAtivas()->get();

        if ($vars->isEmpty()) {
            return $this->preco_formatado;
        }

        $min = (float) $vars->min('preco');
        $max = (float) $vars->max('preco');
        if (abs($min - $max) < 0.01) {
            return 'R$ '.number_format($min, 2, ',', '.');
        }

        return 'R$ '.number_format($min, 2, ',', '.').' – '.number_format($max, 2, ',', '.');
    }
}
