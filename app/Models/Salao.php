<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Salao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'saloes';

    protected $fillable = [
        'nome', 'slug', 'descricao', 'endereco', 'numero', 'complemento',
        'bairro', 'cidade', 'estado', 'cep', 'telefone', 'whatsapp',
        'email', 'site', 'instagram', 'facebook', 'logo', 'foto_capa', 'ativo',
        'latitude', 'longitude',
    ];

    protected $casts = [
        'ativo'     => 'boolean',
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    /**
     * Salão único do sistema (instalação single-tenant).
     * Retorna o primeiro salão ativo.
     */
    public static function principal(): ?self
    {
        return static::where('ativo', true)->orderBy('id')->first();
    }

    /**
     * Id do salão único — atalho seguro para usar como default/override no servidor.
     */
    public static function principalId(): ?int
    {
        return static::principal()?->id;
    }

    /**
     * Distância em km até um ponto (fórmula de Haversine).
     */
    public function distanciaKm(float $lat, float $lng): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $r = 6371; // raio da Terra em km
        $dLat = deg2rad($this->latitude - $lat);
        $dLng = deg2rad($this->longitude - $lng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat)) * cos(deg2rad($this->latitude)) * sin($dLng / 2) ** 2;

        return round($r * 2 * asin(min(1, sqrt($a))), 2);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($salao) {
            if (empty($salao->slug)) {
                $salao->slug = Str::slug($salao->nome);
            }
        });
    }

    /**
     * Quando o salão é resolvido por slug em rotas públicas, exige `ativo = true`.
     * Resoluções por outras chaves (ex.: id em admin) seguem comportamento default.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'slug') {
            return $this->where('slug', $value)->where('ativo', true)->firstOrFail();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /** @return HasOne<ConfiguracaoSalao, $this> */
    public function configuracao(): HasOne
    {
        return $this->hasOne(ConfiguracaoSalao::class);
    }

    /** @return HasMany<Manicure, $this> */
    public function manicures(): HasMany
    {
        return $this->hasMany(Manicure::class)->where('ativo', true);
    }

    /** @return HasMany<Manicure, $this> */
    public function todasManicures(): HasMany
    {
        return $this->hasMany(Manicure::class);
    }

    /** @return HasMany<Servico, $this> */
    public function servicos(): HasMany
    {
        return $this->hasMany(Servico::class)->where('ativo', true);
    }

    /** @return HasMany<CategoriaServico, $this> */
    public function categorias(): HasMany
    {
        return $this->hasMany(CategoriaServico::class)->where('ativo', true)->orderBy('ordem');
    }

    /** @return HasMany<Agendamento, $this> */
    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    /** @return HasMany<Cliente, $this> */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class)->where('ativo', true);
    }

    /** @return HasMany<HorarioFuncionamento, $this> */
    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioFuncionamento::class)->orderBy('dia_semana');
    }

    /** @return HasMany<Folga, $this> */
    public function folgas(): HasMany
    {
        return $this->hasMany(Folga::class);
    }

    /** @return HasMany<Feriado, $this> */
    public function feriados(): HasMany
    {
        return $this->hasMany(Feriado::class);
    }

    /** @return HasMany<Produto, $this> */
    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class)->where('ativo', true);
    }

    /** @return HasMany<Fornecedor, $this> */
    public function fornecedores(): HasMany
    {
        return $this->hasMany(Fornecedor::class);
    }

    /** @return HasMany<GaleriaFoto, $this> */
    public function galeria(): HasMany
    {
        return $this->hasMany(GaleriaFoto::class)->orderBy('ordem')->orderByDesc('id');
    }

    /** @return HasMany<Cupom, $this> */
    public function cupons(): HasMany
    {
        return $this->hasMany(Cupom::class)->where('ativo', true);
    }

    /** @return HasMany<Pacote, $this> */
    public function pacotes(): HasMany
    {
        return $this->hasMany(Pacote::class);
    }

    /** @return HasMany<Avaliacao, $this> */
    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    /** @return HasMany<Caixa, $this> */
    public function caixas(): HasMany
    {
        return $this->hasMany(Caixa::class);
    }

    /** @return HasMany<Despesa, $this> */
    public function despesas(): HasMany
    {
        return $this->hasMany(Despesa::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo && file_exists(public_path('storage/'.$this->logo))) {
            return asset('storage/'.$this->logo);
        }

        return asset('images/logo-default.png');
    }

    public function getEnderecoCompletoAttribute(): string
    {
        $parts = array_filter([
            $this->endereco,
            $this->numero ? 'nº '.$this->numero : null,
            $this->complemento,
            $this->bairro,
            $this->cidade,
            $this->estado,
        ]);

        return implode(', ', $parts);
    }

    public function estaAberto(): bool
    {
        $agora = now();
        $diaSemana = (int) $agora->format('w');
        $horario = $this->horarios()->where('dia_semana', $diaSemana)->where('ativo', true)->first();
        if (! $horario) {
            return false;
        }
        $hora = $agora->format('H:i:s');

        return $hora >= $horario->hora_abertura && $hora <= $horario->hora_fechamento;
    }

    public function getNotaMediaAttribute(): float
    {
        // Se já foi pré-calculado via withAvg('avaliacoes as nota_media_calc', 'nota'), reusa.
        if (array_key_exists('nota_media_calc', $this->attributes)) {
            return round((float) $this->attributes['nota_media_calc'], 1);
        }

        return round($this->avaliacoes()->publicadas()->avg('nota') ?? 0, 1);
    }
}
