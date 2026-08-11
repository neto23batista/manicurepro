<?php

namespace App\Services;

use App\DataTransferObjects\CriarAgendamentoData;
use App\DataTransferObjects\DadosPagamentoData;
use App\Enums\AgendamentoStatus;
use App\Events\AgendamentoCriado;
use App\Events\AgendamentoFinalizado;
use App\Events\AgendamentoReagendado;
use App\Models\Agendamento;
use App\Models\Comanda;
use App\Models\ConfiguracaoSalao;
use App\Models\Cupom;
use App\Models\DisponibilidadeManicure;
use App\Models\Feriado;
use App\Models\Manicure;
use App\Models\Servico;
use App\Models\SlotHold;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AgendaService
{
    public function __construct(
        private ComandaService $comandaService,
        private FidelidadeService $fidelidadeService,
        private PacoteService $pacoteService,
    ) {}

    public function getSlotsDisponiveis(
        Manicure $manicure,
        Carbon $data,
        int $duracaoTotal,
        ?int $agendamentoIgnorar = null
    ): Collection {
        $dataStr = $data->toDateString();
        $ttl = (int) config('manicure.cache_ttl.slots_disponiveis', 60);
        $cacheKey = $this->slotsCacheKey($manicure->id, $dataStr, $duracaoTotal, $agendamentoIgnorar);

        $cached = Cache::remember($cacheKey, $ttl, function () use ($manicure, $data, $dataStr, $duracaoTotal, $agendamentoIgnorar) {
            return $this->gerarSlotsDisponiveis($manicure, $data, $dataStr, $duracaoTotal, $agendamentoIgnorar)
                ->values()
                ->all();
        });

        return collect($cached);
    }

    /**
     * Invalida o cache de slots da manicure na data (todas as durações / ignore ids).
     */
    public function invalidarCacheSlots(int $manicureId, Carbon|string $data): void
    {
        $dataStr = Carbon::parse($data)->toDateString();
        $verKey = $this->slotsCacheVersionKey($manicureId, $dataStr);

        if (! Cache::has($verKey)) {
            Cache::forever($verKey, 1);
        }

        Cache::increment($verKey);
    }

    /**
     * Invalida slots de todas as manicures do salão na data (ex.: folga do salão).
     */
    public function invalidarCacheSlotsSalao(int $salaoId, Carbon|string $data): void
    {
        Manicure::where('salao_id', $salaoId)
            ->pluck('id')
            ->each(fn (int $id) => $this->invalidarCacheSlots($id, $data));
    }

    /**
     * Invalida ocorrências próximas do feriado recorrente (ano atual + próximo).
     */
    public function invalidarCacheSlotsFeriado(Feriado $feriado): void
    {
        $anoAtual = (int) now()->year;
        foreach ([$anoAtual, $anoAtual + 1] as $ano) {
            if (! checkdate($feriado->mes, $feriado->dia, $ano)) {
                continue;
            }
            $data = Carbon::create($ano, $feriado->mes, $feriado->dia)->startOfDay();
            $this->invalidarCacheSlotsSalao((int) $feriado->salao_id, $data);
        }
    }

    /**
     * Invalida slots dos próximos ~60 dias que caem no dia_semana da disponibilidade.
     */
    public function invalidarCacheSlotsDisponibilidade(DisponibilidadeManicure $disp): void
    {
        $manicureId = (int) $disp->manicure_id;
        $diaSemana = (int) $disp->dia_semana;
        $cursor = Carbon::today();

        for ($i = 0; $i < 60; $i++) {
            $dia = $cursor->copy()->addDays($i);
            if ((int) $dia->format('w') === $diaSemana) {
                $this->invalidarCacheSlots($manicureId, $dia);
            }
        }
    }

    private function slotsCacheKey(int $manicureId, string $dataStr, int $duracao, ?int $ignoreId): string
    {
        $ver = (int) Cache::get($this->slotsCacheVersionKey($manicureId, $dataStr), 0);
        $ignore = $ignoreId ?? 0;

        return "agenda:slots:{$manicureId}:{$dataStr}:{$duracao}:{$ignore}:v{$ver}";
    }

    private function slotsCacheVersionKey(int $manicureId, string $dataStr): string
    {
        return "agenda:slots:ver:{$manicureId}:{$dataStr}";
    }

    private function gerarSlotsDisponiveis(
        Manicure $manicure,
        Carbon $data,
        string $dataStr,
        int $duracaoTotal,
        ?int $agendamentoIgnorar
    ): Collection {
        $slots = collect();
        $diaSemana = (int) $data->format('w');

        // Horários/disponibilidades: eager-load (estáveis no request).
        // Folgas/feriados do dia: query pontual (evita relação stale após create/delete no mesmo request).
        $manicure->loadMissing([
            'salao.horarios',
            'disponibilidades',
        ]);

        $salao = $manicure->salao;
        if (! $salao) {
            return $slots;
        }

        $feriado = $this->feriadoDoDia($salao->id, $data);
        if ($feriado && $feriado->dia_todo) {
            return $slots;
        }

        $folgaSalao = $salao->folgas()->whereDate('data', $dataStr)->first();
        if ($folgaSalao && $folgaSalao->dia_todo) {
            return $slots;
        }

        $folgaManicure = $manicure->folgas()->whereDate('data', $dataStr)->first();
        if ($folgaManicure && $folgaManicure->dia_todo) {
            return $slots;
        }

        $horarioSalao = $salao->horarios->first(
            fn ($h) => (int) $h->dia_semana === $diaSemana && $h->ativo,
        );
        if (! $horarioSalao) {
            return $slots;
        }

        $dispManicure = $manicure->disponibilidades->first(
            fn ($d) => (int) $d->dia_semana === $diaSemana && $d->ativo,
        );
        if (! $dispManicure) {
            return $slots;
        }

        $config = ConfiguracaoSalao::paraSalao($salao->id);
        $intervalo = ($config !== null ? $config->intervalo_agendamento : null)
            ?? config('manicure.agenda.intervalo_default', 30);

        $inicio = max(
            strtotime($dataStr.' '.$horarioSalao->hora_abertura),
            strtotime($dataStr.' '.$dispManicure->hora_inicio),
        );
        $fim = min(
            strtotime($dataStr.' '.$horarioSalao->hora_fechamento),
            strtotime($dataStr.' '.$dispManicure->hora_fim),
        );

        $agendamentosExistentes = Agendamento::where('manicure_id', $manicure->id)
            ->whereDate('data_hora_inicio', $dataStr)
            ->whereNotIn('status', [
                AgendamentoStatus::Cancelado->value,
                AgendamentoStatus::NaoCompareceu->value,
            ])
            ->when($agendamentoIgnorar, fn ($q) => $q->where('id', '!=', $agendamentoIgnorar))
            ->get(['data_hora_inicio', 'data_hora_fim']);

        $holdsAtivos = SlotHold::where('manicure_id', $manicure->id)
            ->whereDate('data_hora_inicio', $dataStr)
            ->where('expires_at', '>', now())
            ->get(['data_hora_inicio', 'data_hora_fim']);

        $ocupados = $agendamentosExistentes
            ->concat($holdsAtivos)
            ->map(fn ($item) => [
                'inicio' => Carbon::parse($item->data_hora_inicio),
                'fim'    => Carbon::parse($item->data_hora_fim),
            ]);

        // Bloqueios parciais (folga/feriado) e pausa/almoço entram como ocupados.
        foreach ($this->bloqueiosParciaisDoDia($dataStr, $feriado, $folgaSalao, $folgaManicure, $dispManicure) as $bloqueio) {
            $ocupados->push($bloqueio);
        }

        $cursor = $inicio;
        while ($cursor + ($duracaoTotal * 60) <= $fim) {
            $slotInicio = Carbon::createFromTimestamp($cursor, config('app.timezone'));
            $slotFim = $slotInicio->copy()->addMinutes($duracaoTotal);

            if (! $this->temConflito($ocupados, $slotInicio, $slotFim)) {
                $slots->push([
                    'hora'       => $slotInicio->format('H:i'),
                    'datetime'   => $slotInicio->toDateTimeString(),
                    'disponivel' => true,
                ]);
            }

            $cursor += $intervalo * 60;
        }

        return $slots;
    }

    /**
     * @return list<array{inicio: Carbon, fim: Carbon}>
     */
    private function bloqueiosParciaisDoDia(
        string $dataStr,
        ?Feriado $feriado,
        mixed $folgaSalao,
        mixed $folgaManicure,
        DisponibilidadeManicure $dispManicure,
    ): array {
        $bloqueios = [];

        foreach ([$feriado, $folgaSalao, $folgaManicure] as $bloco) {
            if (! $bloco || $bloco->dia_todo || ! $bloco->hora_inicio || ! $bloco->hora_fim) {
                continue;
            }
            $bloqueios[] = [
                'inicio' => Carbon::parse($dataStr.' '.$bloco->hora_inicio),
                'fim'    => Carbon::parse($dataStr.' '.$bloco->hora_fim),
            ];
        }

        if ($dispManicure->temPausa()) {
            $bloqueios[] = [
                'inicio' => Carbon::parse($dataStr.' '.$dispManicure->pausa_inicio),
                'fim'    => Carbon::parse($dataStr.' '.$dispManicure->pausa_fim),
            ];
        }

        return $bloqueios;
    }

    private function feriadoDoDia(int $salaoId, Carbon $data): ?Feriado
    {
        return Feriado::where('salao_id', $salaoId)
            ->where('ativo', true)
            ->where('mes', (int) $data->month)
            ->where('dia', (int) $data->day)
            ->first();
    }

    private function temConflito(Collection $ocupados, Carbon $inicio, Carbon $fim): bool
    {
        foreach ($ocupados as $ag) {
            if ($inicio < $ag['fim'] && $fim > $ag['inicio']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Soft rules (horário, folga, feriado, pausa). Encaixe pula esta checagem.
     */
    public function horarioDentroDisponibilidade(Manicure $manicure, Carbon $inicio, Carbon $fim): bool
    {
        $dataStr = $inicio->toDateString();
        $diaSemana = (int) $inicio->format('w');

        $manicure->loadMissing(['salao.horarios', 'disponibilidades']);
        $salao = $manicure->salao;
        if (! $salao) {
            return false;
        }

        $feriado = $this->feriadoDoDia($salao->id, $inicio);
        if ($feriado && $feriado->dia_todo) {
            return false;
        }

        $folgaSalao = $salao->folgas()->whereDate('data', $dataStr)->first();
        if ($folgaSalao && $folgaSalao->dia_todo) {
            return false;
        }

        $folgaManicure = $manicure->folgas()->whereDate('data', $dataStr)->first();
        if ($folgaManicure && $folgaManicure->dia_todo) {
            return false;
        }

        $horarioSalao = $salao->horarios->first(
            fn ($h) => (int) $h->dia_semana === $diaSemana && $h->ativo,
        );
        $dispManicure = $manicure->disponibilidades->first(
            fn ($d) => (int) $d->dia_semana === $diaSemana && $d->ativo,
        );

        if (! $horarioSalao || ! $dispManicure) {
            return false;
        }

        $janelaInicio = max(
            strtotime($dataStr.' '.$horarioSalao->hora_abertura),
            strtotime($dataStr.' '.$dispManicure->hora_inicio),
        );
        $janelaFim = min(
            strtotime($dataStr.' '.$horarioSalao->hora_fechamento),
            strtotime($dataStr.' '.$dispManicure->hora_fim),
        );

        if ($inicio->timestamp < $janelaInicio || $fim->timestamp > $janelaFim) {
            return false;
        }

        $bloqueios = collect($this->bloqueiosParciaisDoDia(
            $dataStr,
            $feriado,
            $folgaSalao,
            $folgaManicure,
            $dispManicure,
        ));

        return ! $this->temConflito($bloqueios, $inicio, $fim);
    }

    /**
     * Cria um agendamento. Aceita DTO tipado ou array (compat retroativa).
     *
     * Serializa criações da mesma manicure com lock pessimista na linha da manicure,
     * evitando double-book quando duas requisições passam no check de conflito em paralelo.
     */
    public function criarAgendamento(CriarAgendamentoData|array $dados): Agendamento
    {
        $data = $dados instanceof CriarAgendamentoData ? $dados : CriarAgendamentoData::fromArray($dados);

        return DB::transaction(function () use ($data) {
            $servicos = Servico::whereIn('id', $data->servicoIds)->get();
            if ($servicos->count() !== count(array_unique($data->servicoIds))) {
                throw ValidationException::withMessages([
                    'error' => 'Um ou mais serviços são inválidos.',
                ]);
            }

            $linhas = [];
            $duracaoTotal = 0;
            $valorTotal = 0.0;

            foreach ($servicos as $servico) {
                $variacaoId = $data->servicoVariacoes[$servico->id] ?? null;
                $variacao = null;

                if ($variacaoId) {
                    $variacao = \App\Models\ServicoVariacao::query()
                        ->whereKey($variacaoId)
                        ->where('servico_id', $servico->id)
                        ->where('ativo', true)
                        ->first();

                    if (! $variacao) {
                        throw ValidationException::withMessages([
                            'error' => "Variação inválida para o serviço {$servico->nome}.",
                        ]);
                    }
                }

                $preco = (float) ($variacao?->preco ?? $servico->preco);
                $duracao = (int) ($variacao?->duracao ?? $servico->duracao);
                $duracaoTotal += $duracao;
                $valorTotal += $preco;

                $linhas[] = [
                    'servico'     => $servico,
                    'variacao_id' => $variacao?->id,
                    'preco'       => $preco,
                    'duracao'     => $duracao,
                ];
            }

            $manicure = $this->travarManicure($data->manicureId);
            $dataHoraInicio = Carbon::parse($data->dataHoraInicio);
            $dataHoraFim = $dataHoraInicio->copy()->addMinutes($duracaoTotal);

            if ($this->temConflitoNoBanco($manicure->id, $dataHoraInicio, $dataHoraFim)) {
                throw ValidationException::withMessages([
                    'error' => 'Horário indisponível. Por favor, escolha outro horário.',
                ]);
            }

            // Encaixe (só staff): permite fora de disponibilidade, mas nunca overlap.
            if (! $data->encaixe && ! $this->horarioDentroDisponibilidade($manicure, $dataHoraInicio, $dataHoraFim)) {
                throw ValidationException::withMessages([
                    'error' => 'Horário fora da disponibilidade. Por favor, escolha outro horário.',
                ]);
            }

            $desconto = 0;
            $cupomIdAplicado = null;
            if ($data->cupomId) {
                $cupom = Cupom::find($data->cupomId);
                if (! $cupom) {
                    throw ValidationException::withMessages([
                        'error' => 'Cupom não encontrado.',
                    ]);
                }
                // Valida validade / uso máximo / salão / regras avançadas e consome 1 uso com lock.
                $desconto = $cupom->aplicarPara(
                    $data->salaoId,
                    $valorTotal,
                    $data->clienteId,
                    $data->servicoIds,
                );
                $cupomIdAplicado = $cupom->id;
            }

            $agendamento = Agendamento::create([
                'salao_id'         => $data->salaoId,
                'cliente_id'       => $data->clienteId,
                'manicure_id'      => $data->manicureId,
                'user_id'          => $data->userId,
                'data_hora_inicio' => $dataHoraInicio,
                'data_hora_fim'    => $dataHoraFim,
                'status'           => $data->status->value,
                'observacoes'      => $data->observacoes,
                'origem'           => $data->origem,
                'encaixe'          => $data->encaixe,
                'valor_total'      => $valorTotal,
                'valor_desconto'   => $desconto,
                'cupom_id'         => $cupomIdAplicado,
                'nome_cliente'     => $data->nomeCliente,
                'telefone_cliente' => $data->telefoneCliente,
            ]);

            foreach ($linhas as $linha) {
                $agendamento->servicos()->attach($linha['servico']->id, [
                    'preco'                => $linha['preco'],
                    'duracao'              => $linha['duracao'],
                    'servico_variacao_id'  => $linha['variacao_id'],
                ]);
            }

            // Libera reservas temporárias que cobriam este horário
            SlotHold::where('manicure_id', $manicure->id)
                ->where('data_hora_inicio', '<', $dataHoraFim)
                ->where('data_hora_fim', '>', $dataHoraInicio)
                ->delete();

            $this->invalidarCacheSlots($manicure->id, $dataHoraInicio);

            AgendamentoCriado::dispatch($agendamento);

            return $agendamento;
        });
    }

    /**
     * Verifica se um slot está livre (sem agendamento nem outra reserva ativa),
     * ignorando opcionalmente um token de reserva do próprio solicitante.
     */
    public function slotDisponivel(int $manicureId, Carbon $inicio, Carbon $fim, ?string $excetoToken = null): bool
    {
        if ($this->temConflitoNoBanco($manicureId, $inicio, $fim)) {
            return false;
        }

        return ! SlotHold::where('manicure_id', $manicureId)
            ->where('expires_at', '>', now())
            ->when($excetoToken, fn ($q) => $q->where('token', '!=', $excetoToken))
            ->where('data_hora_inicio', '<', $fim)
            ->where('data_hora_fim', '>', $inicio)
            ->exists();
    }

    /**
     * Cria/renova uma reserva temporária para o token informado.
     * Retorna null se o horário não estiver disponível.
     */
    public function criarHold(int $manicureId, Carbon $inicio, int $duracao, string $token): ?SlotHold
    {
        $fim = $inicio->copy()->addMinutes($duracao);

        return DB::transaction(function () use ($manicureId, $inicio, $fim, $token) {
            $this->travarManicure($manicureId);

            // Remove reservas anteriores do mesmo solicitante
            SlotHold::where('token', $token)->delete();

            if (! $this->slotDisponivel($manicureId, $inicio, $fim, $token)) {
                return null;
            }

            $minutos = (int) config('manicure.agenda.hold_minutos', 10);

            $hold = SlotHold::create([
                'manicure_id'      => $manicureId,
                'data_hora_inicio' => $inicio,
                'data_hora_fim'    => $fim,
                'token'            => $token,
                'expires_at'       => now()->addMinutes($minutos),
            ]);

            $this->invalidarCacheSlots($manicureId, $inicio);

            return $hold;
        });
    }

    public function limparHoldsExpirados(): int
    {
        return SlotHold::where('expires_at', '<=', now())->delete();
    }

    /**
     * Trava a linha da manicure para serializar create/hold/reagendar concorrentes.
     */
    private function travarManicure(int $manicureId): Manicure
    {
        return Manicure::whereKey($manicureId)->lockForUpdate()->firstOrFail();
    }

    /**
     * Cria uma série de agendamentos recorrentes a partir de um agendamento base.
     * Ocorrências em conflito são puladas (não interrompem a série).
     *
     * @return array{criados: list<Agendamento>, pulados: list<string>}
     */
    public function criarRecorrente(array $dados, string $frequencia, int $ocorrencias): array
    {
        $ocorrencias = max(1, min(12, $ocorrencias));
        $base = Carbon::parse($dados['data_hora_inicio']);

        $criados = [];
        $pulados = [];

        for ($i = 0; $i < $ocorrencias; $i++) {
            $inicio = match ($frequencia) {
                'semanal'   => $base->copy()->addWeeks($i),
                'quinzenal' => $base->copy()->addWeeks($i * 2),
                'mensal'    => $base->copy()->addMonthsNoOverflow($i),
                default     => $base->copy(),
            };

            $ocorrencia = array_merge($dados, ['data_hora_inicio' => $inicio->toDateTimeString()]);

            try {
                $criados[] = $this->criarAgendamento($ocorrencia);
            } catch (ValidationException $e) {
                $msgs = collect($e->errors())->flatten()->implode(' ');
                if (! str_contains($msgs, 'Horário indisponível')
                    && ! str_contains($msgs, 'fora da disponibilidade')) {
                    throw $e;
                }
                $pulados[] = $inicio->format('d/m/Y H:i');
            }
        }

        return ['criados' => $criados, 'pulados' => $pulados];
    }

    public function finalizarAtendimento(Agendamento $agendamento, DadosPagamentoData|array $dadosPagamento): Comanda
    {
        $dto = $dadosPagamento instanceof DadosPagamentoData
            ? $dadosPagamento
            : DadosPagamentoData::fromArray($dadosPagamento);

        return DB::transaction(function () use ($agendamento, $dto) {
            $agendamento->update(['status' => AgendamentoStatus::Concluido->value]);

            $comanda = $this->comandaService->fecharComanda($agendamento, $dto);

            $this->fidelidadeService->creditarPorAtendimento($agendamento, (float) $comanda->total);
            $this->pacoteService->consumirSessao($agendamento);

            AgendamentoFinalizado::dispatch($agendamento, $comanda);

            return $comanda;
        });
    }

    private function temConflitoNoBanco(int $manicureId, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): bool
    {
        return Agendamento::where('manicure_id', $manicureId)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->whereNotIn('status', [
                AgendamentoStatus::Cancelado->value,
                AgendamentoStatus::NaoCompareceu->value,
            ])
            ->where(function ($q) use ($inicio, $fim) {
                $q->where(function ($q2) use ($inicio, $fim) {
                    $q2->where('data_hora_inicio', '<', $fim)
                        ->where('data_hora_fim', '>', $inicio);
                });
            })
            ->exists();
    }

    /**
     * Remarca um agendamento para um novo horário, preservando os serviços
     * (e portanto a duração). Revalida o conflito dentro de transação.
     */
    public function reagendar(Agendamento $agendamento, Carbon $novoInicio): Agendamento
    {
        return DB::transaction(function () use ($agendamento, $novoInicio) {
            $this->travarManicure($agendamento->manicure_id);
            $agendamento->refresh()->loadMissing('servicos');

            $duracao = (int) $agendamento->servicos->sum(fn ($s) => $s->pivot->duracao);
            if ($duracao <= 0) {
                $duracao = (int) $agendamento->data_hora_inicio->diffInMinutes($agendamento->data_hora_fim);
            }

            $novoFim = $novoInicio->copy()->addMinutes($duracao);

            if ($this->temConflitoNoBanco($agendamento->manicure_id, $novoInicio, $novoFim, $agendamento->id)) {
                throw ValidationException::withMessages([
                    'error' => 'Horário indisponível. Por favor, escolha outro horário.',
                ]);
            }

            // Encaixe original pode remarcar fora da grade; demais respeitam disponibilidade.
            if (! $agendamento->encaixe) {
                $manicure = Manicure::findOrFail($agendamento->manicure_id);
                if (! $this->horarioDentroDisponibilidade($manicure, $novoInicio, $novoFim)) {
                    throw ValidationException::withMessages([
                        'error' => 'Horário fora da disponibilidade. Por favor, escolha outro horário.',
                    ]);
                }
            }

            $dataAnterior = $agendamento->data_hora_inicio->copy();

            $agendamento->update([
                'data_hora_inicio' => $novoInicio,
                'data_hora_fim'    => $novoFim,
            ]);

            $this->invalidarCacheSlots($agendamento->manicure_id, $dataAnterior);
            $this->invalidarCacheSlots($agendamento->manicure_id, $novoInicio);

            AgendamentoReagendado::dispatch($agendamento, $dataAnterior);

            return $agendamento;
        });
    }

    public function getDatasDisponiveis(Manicure $manicure, int $dias = 30): Collection
    {
        $datas = collect();
        $hoje = Carbon::today();
        $config = ConfiguracaoSalao::paraSalao($manicure->salao_id);
        $antecedenciaMinima = ($config !== null ? $config->antecedencia_minima : null)
            ?? config('manicure.agenda.antecedencia_min', 1);
        $antecedenciaMaxima = ($config !== null ? $config->antecedencia_maxima : null)
            ?? config('manicure.agenda.antecedencia_max', 30);

        $manicure->loadMissing(['disponibilidades', 'folgas', 'salao.folgas', 'salao.feriados']);

        $diasAtivos = $manicure->disponibilidades
            ->where('ativo', true)
            ->pluck('dia_semana')
            ->map(fn ($d) => (int) $d)
            ->all();

        $diasFolga = $manicure->folgas
            ->filter(fn ($f) => $f->dia_todo && $f->data->toDateString() >= $hoje->toDateString())
            ->map(fn ($f) => $f->data->toDateString())
            ->all();

        $diasFolgaSalao = $manicure->salao->folgas
            ->filter(fn ($f) => $f->dia_todo && $f->data->toDateString() >= $hoje->toDateString())
            ->map(fn ($f) => $f->data->toDateString())
            ->all();

        $feriadosDiaTodo = $manicure->salao->feriados
            ->filter(fn ($f) => $f->ativo && $f->dia_todo)
            ->map(fn ($f) => sprintf('%02d-%02d', $f->mes, $f->dia))
            ->all();

        for ($i = $antecedenciaMinima; $i <= $antecedenciaMaxima; $i++) {
            $data = $hoje->copy()->addDays($i);
            $diaSemana = (int) $data->format('w');

            if (! in_array($diaSemana, $diasAtivos, true)) {
                continue;
            }
            if (in_array($data->toDateString(), $diasFolga, true)) {
                continue;
            }
            if (in_array($data->toDateString(), $diasFolgaSalao, true)) {
                continue;
            }
            if (in_array(sprintf('%02d-%02d', (int) $data->month, (int) $data->day), $feriadosDiaTodo, true)) {
                continue;
            }

            $datas->push($data->toDateString());
        }

        return $datas;
    }
}
