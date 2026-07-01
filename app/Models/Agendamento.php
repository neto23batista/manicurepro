<?php

namespace App\Models;

use App\Enums\AgendamentoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'salao_id', 'cliente_id', 'manicure_id', 'user_id',
        'data_hora_inicio', 'data_hora_fim', 'status', 'observacoes',
        'observacoes_internas', 'origem', 'valor_total', 'valor_desconto',
        'cupom_id', 'nome_cliente', 'telefone_cliente',
        'confirmado_em', 'lembrete_24h_em', 'lembrete_2h_em',
        'mp_payment_id', 'sinal_status', 'sinal_valor',
    ];

    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'valor_total' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'confirmado_em' => 'datetime',
        'lembrete_24h_em' => 'datetime',
        'lembrete_2h_em' => 'datetime',
        'sinal_valor' => 'decimal:2',
    ];

    /** Rótulos de status (value => label) para selects e filtros. */
    public const STATUS_LABELS = [
        'aguardando'     => 'Aguardando',
        'confirmado'     => 'Confirmado',
        'em_andamento'   => 'Em Andamento',
        'concluido'      => 'Concluído',
        'cancelado'      => 'Cancelado',
        'nao_compareceu' => 'Não Compareceu',
    ];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function manicure()
    {
        return $this->belongsTo(Manicure::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function servicos()
    {
        return $this->belongsToMany(Servico::class, 'agendamento_servicos')
            ->withPivot('preco', 'duracao')
            ->withTimestamps();
    }

    public function comanda()
    {
        return $this->hasOne(Comanda::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    public function avaliacao()
    {
        return $this->hasOne(Avaliacao::class);
    }

    public function cupom()
    {
        return $this->belongsTo(Cupom::class);
    }

    public function statusEnum(): ?AgendamentoStatus
    {
        return $this->status ? AgendamentoStatus::tryFrom($this->status) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->statusEnum()?->label() ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return $this->statusEnum()?->color() ?? 'secondary';
    }

    public function getNomeClienteExibidoAttribute(): string
    {
        return $this->cliente?->nome ?? $this->nome_cliente ?? 'Cliente não identificado';
    }

    public function getDuracaoMinutosAttribute(): int
    {
        return $this->data_hora_inicio->diffInMinutes($this->data_hora_fim);
    }

    public function podeSerCancelado(): bool
    {
        return $this->statusEnum()?->podeSerCancelado() ?? false;
    }

    public function podeSerReagendado(): bool
    {
        return $this->statusEnum()?->podeSerReagendado() ?? false;
    }

    public function sinalPago(): bool
    {
        return $this->sinal_status === 'pago';
    }

    public function sinalPendente(): bool
    {
        return $this->sinal_status === 'pendente';
    }

    public function precisaSinal(): bool
    {
        return $this->sinal_status !== 'pago'
            && (bool) config('manicure.pagamento.mercadopago.enabled')
            && (bool) config('manicure.pagamento.sinal.habilitado')
            && in_array($this->status, ['aguardando', 'confirmado'], true);
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data_hora_inicio', today());
    }

    public function scopeAtivos($query)
    {
        return $query->whereNotIn('status', [
            AgendamentoStatus::Cancelado->value,
            AgendamentoStatus::NaoCompareceu->value,
        ]);
    }

    public function scopePorStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConcluidos($query)
    {
        return $query->where('status', AgendamentoStatus::Concluido->value);
    }

    public function scopeCancelados($query)
    {
        return $query->where('status', AgendamentoStatus::Cancelado->value);
    }

    public function scopeNaoCompareceu($query)
    {
        return $query->where('status', AgendamentoStatus::NaoCompareceu->value);
    }

    public function scopeDoMes($query, ?int $mes = null, ?int $ano = null)
    {
        $mes ??= now()->month;
        $ano ??= now()->year;
        return $query->whereMonth('data_hora_inicio', $mes)
                     ->whereYear('data_hora_inicio', $ano);
    }

    public function scopeDoSalao($query, int $salaoId)
    {
        return $query->where('salao_id', $salaoId);
    }

    public function scopeDaManicure($query, int $manicureId)
    {
        return $query->where('manicure_id', $manicureId);
    }

    public function scopeEntre($query, \Carbon\Carbon $inicio, \Carbon\Carbon $fim)
    {
        return $query->whereBetween('data_hora_inicio', [$inicio, $fim]);
    }
}
