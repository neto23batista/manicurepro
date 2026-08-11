<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'salao_id', 'nome', 'email', 'telefone', 'cpf',
        'data_nascimento', 'aniversario_enviado_em', 'endereco', 'observacoes', 'alergias',
        'notas_unhas', 'cores_preferidas', 'contraindicacoes', 'ultima_formula',
        'total_visitas', 'total_gasto', 'pontos_fidelidade', 'total_faltas',
        'codigo_indicacao', 'indicado_por_cliente_id', 'ativo',
    ];

    protected $casts = [
        'data_nascimento'        => 'date',
        'aniversario_enviado_em' => 'date',
        'ativo'                  => 'boolean',
        'total_gasto'            => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cliente $cliente) {
            if (empty($cliente->codigo_indicacao)) {
                $cliente->codigo_indicacao = static::gerarCodigoIndicacaoUnico();
            }
        });
    }

    public static function gerarCodigoIndicacaoUnico(): string
    {
        do {
            $codigo = strtoupper(Str::random(8));
        } while (static::withTrashed()->where('codigo_indicacao', $codigo)->exists());

        return $codigo;
    }

    /**
     * Garante que o cliente tenha um código de indicação (clientes legados).
     */
    public function garantirCodigoIndicacao(): string
    {
        if ($this->codigo_indicacao) {
            return $this->codigo_indicacao;
        }

        $this->codigo_indicacao = static::gerarCodigoIndicacaoUnico();
        $this->save();

        return $this->codigo_indicacao;
    }

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

    /** @return BelongsTo<Cliente, $this> */
    public function indicadoPor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'indicado_por_cliente_id');
    }

    /** @return HasMany<Cliente, $this> */
    public function indicados(): HasMany
    {
        return $this->hasMany(self::class, 'indicado_por_cliente_id');
    }

    /** @return HasMany<Agendamento, $this> */
    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    /** @return HasMany<Avaliacao, $this> */
    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    /** @return HasMany<FidelidadePonto, $this> */
    public function pontosFidelidade(): HasMany
    {
        return $this->hasMany(FidelidadePonto::class);
    }

    /** @return HasMany<ListaEspera, $this> */
    public function listasEspera(): HasMany
    {
        return $this->hasMany(ListaEspera::class);
    }

    /** @return HasMany<Comanda, $this> */
    public function comandas(): HasMany
    {
        return $this->hasMany(Comanda::class);
    }

    /** @return HasMany<ClientePacote, $this> */
    public function pacotes(): HasMany
    {
        return $this->hasMany(ClientePacote::class);
    }

    /** @return HasMany<ClienteFichaHistorico, $this> */
    public function fichaHistorico(): HasMany
    {
        return $this->hasMany(ClienteFichaHistorico::class)->latest();
    }

    public function getAniversarioHojeAttribute(): bool
    {
        if (! $this->data_nascimento) {
            return false;
        }

        return $this->data_nascimento->format('d-m') === now()->format('d-m');
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento ? $this->data_nascimento->age : null;
    }

    public function getEhRiscoNoShowAttribute(): bool
    {
        return $this->total_faltas >= $this->limiteAlertaNoShow();
    }

    public function limiteAlertaNoShow(): int
    {
        $config = ConfiguracaoSalao::paraSalao((int) $this->salao_id);

        return (int) (($config !== null ? $config->limite_alerta_no_show : null)
            ?? config('manicure.no_show.limite_alerta', 2));
    }

    /**
     * Localiza cliente do salão pelo telefone (compara só dígitos, ignora máscara/DDI).
     */
    public static function findByTelefoneNoSalao(int $salaoId, string $telefone): ?self
    {
        $digits = preg_replace('/\D+/', '', $telefone) ?: '';
        if ($digits === '') {
            return null;
        }

        $local = (str_starts_with($digits, '55') && strlen($digits) >= 12)
            ? substr($digits, 2)
            : $digits;

        return static::query()
            ->where('salao_id', $salaoId)
            ->whereNotNull('telefone')
            ->get()
            ->first(function (self $cliente) use ($local, $digits) {
                $cDigits = preg_replace('/\D+/', '', (string) $cliente->telefone) ?: '';
                $cLocal = (str_starts_with($cDigits, '55') && strlen($cDigits) >= 12)
                    ? substr($cDigits, 2)
                    : $cDigits;

                return $cLocal === $local || $cDigits === $digits;
            });
    }

    /**
     * Cria ou atualiza cliente guest pelo telefone do salão (sem user obrigatório).
     */
    public static function findOrCreateGuest(int $salaoId, string $nome, string $telefone, ?string $email = null): self
    {
        $cliente = static::findByTelefoneNoSalao($salaoId, $telefone);

        if ($cliente) {
            $cliente->fill(array_filter([
                'nome'  => $nome,
                'email' => $email ?: $cliente->email,
            ], fn ($v) => $v !== null && $v !== ''));
            $cliente->telefone = $telefone;
            $cliente->save();

            return $cliente;
        }

        return static::create([
            'salao_id' => $salaoId,
            'nome'     => $nome,
            'telefone' => $telefone,
            'email'    => $email,
            'ativo'    => true,
        ]);
    }
}
