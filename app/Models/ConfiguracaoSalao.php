<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ConfiguracaoSalao extends Model
{
    protected $table = 'configuracoes_salao';

    protected $fillable = [
        'salao_id', 'cor_primaria', 'permitir_agendamento_online',
        'intervalo_agendamento', 'antecedencia_minima', 'antecedencia_maxima',
        'cancelamento_prazo', 'taxa_cancelamento', 'fidelidade_ativo',
        'pontos_por_real', 'pontos_para_desconto', 'valor_desconto_pontos',
        'notificar_email', 'notificar_whatsapp', 'mensagem_confirmacao',
        'mensagem_lembrete', 'lembrete_horas', 'limite_alerta_no_show',
        'role_permissions', 'onboarding_completed_at', 'onboarding_dismissed_at',
    ];

    protected $casts = [
        'permitir_agendamento_online' => 'boolean',
        'fidelidade_ativo'            => 'boolean',
        'notificar_email'             => 'boolean',
        'notificar_whatsapp'          => 'boolean',
        'intervalo_agendamento'       => 'integer',
        'antecedencia_minima'         => 'integer',
        'antecedencia_maxima'         => 'integer',
        'cancelamento_prazo'          => 'integer',
        'pontos_por_real'             => 'integer',
        'pontos_para_desconto'        => 'integer',
        'lembrete_horas'              => 'integer',
        'limite_alerta_no_show'       => 'integer',
        'taxa_cancelamento'           => 'decimal:2',
        'valor_desconto_pontos'       => 'decimal:2',
        'role_permissions'            => 'array',
        'onboarding_completed_at'     => 'datetime',
        'onboarding_dismissed_at'     => 'datetime',
    ];
    protected static function booted(): void
    {
        $invalidate = fn (ConfiguracaoSalao $c) => self::esquecerCache((int) $c->salao_id);

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    public static function paraSalao(int $salaoId): ?self
    {
        return Cache::remember(
            self::cacheKey($salaoId),
            config('manicure.cache_ttl.configuracao_salao', 3600),
            fn () => self::where('salao_id', $salaoId)->first(),
        );
    }

    public static function esquecerCache(int $salaoId): void
    {
        Cache::forget(self::cacheKey($salaoId));
    }

    private static function cacheKey(int $salaoId): string
    {
        return "salao:{$salaoId}:configuracao";
    }
}
