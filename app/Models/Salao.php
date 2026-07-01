<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'ativo' => 'boolean',
        'latitude' => 'float',
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

    public function configuracao()
    {
        return $this->hasOne(ConfiguracaoSalao::class);
    }

    public function manicures()
    {
        return $this->hasMany(Manicure::class)->where('ativo', true);
    }

    public function todasManicures()
    {
        return $this->hasMany(Manicure::class);
    }

    public function servicos()
    {
        return $this->hasMany(Servico::class)->where('ativo', true);
    }

    public function categorias()
    {
        return $this->hasMany(CategoriaServico::class)->where('ativo', true)->orderBy('ordem');
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class)->where('ativo', true);
    }

    public function horarios()
    {
        return $this->hasMany(HorarioFuncionamento::class)->orderBy('dia_semana');
    }

    public function folgas()
    {
        return $this->hasMany(Folga::class);
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class)->where('ativo', true);
    }

    public function galeria()
    {
        return $this->hasMany(GaleriaFoto::class)->orderBy('ordem')->orderByDesc('id');
    }

    public function cupons()
    {
        return $this->hasMany(Cupom::class)->where('ativo', true);
    }

    public function avaliacoes()
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo && file_exists(public_path('storage/' . $this->logo))) {
            return asset('storage/' . $this->logo);
        }
        return asset('images/logo-default.png');
    }

    public function getEnderecoCompletoAttribute(): string
    {
        $parts = array_filter([
            $this->endereco,
            $this->numero ? 'nº ' . $this->numero : null,
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
        if (!$horario) return false;
        $hora = $agora->format('H:i:s');
        return $hora >= $horario->hora_abertura && $hora <= $horario->hora_fechamento;
    }

    public function getNotaMediaAttribute(): float
    {
        // Se já foi pré-calculado via withAvg('avaliacoes as nota_media_calc', 'nota'), reusa.
        if (array_key_exists('nota_media_calc', $this->attributes)) {
            return round((float) $this->attributes['nota_media_calc'], 1);
        }
        return round($this->avaliacoes()->avg('nota') ?? 0, 1);
    }
}
