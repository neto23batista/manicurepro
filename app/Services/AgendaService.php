<?php

namespace App\Services;

use App\DataTransferObjects\CriarAgendamentoData;
use App\DataTransferObjects\DadosPagamentoData;
use App\Enums\AgendamentoStatus;
use App\Events\AgendamentoCriado;
use App\Events\AgendamentoFinalizado;
use App\Models\Agendamento;
use App\Models\Comanda;
use App\Models\ConfiguracaoSalao;
use App\Models\Cupom;
use App\Models\Manicure;
use App\Models\Servico;
use App\Models\SlotHold;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgendaService
{
    public function __construct(
        private ComandaService $comandaService,
        private FidelidadeService $fidelidadeService,
    ) {}

    public function getSlotsDisponiveis(
        Manicure $manicure,
        Carbon $data,
        int $duracaoTotal,
        ?int $agendamentoIgnorar = null
    ): Collection {
        $slots = collect();
        $salao = $manicure->salao;
        $diaSemana = (int) $data->format('w');

        // Folga do salão
        $folgaSalao = $salao->folgas()->whereDate('data', $data->toDateString())->first();
        if ($folgaSalao && $folgaSalao->dia_todo) return $slots;

        // Folga da manicure
        $folgaManicure = $manicure->folgas()->whereDate('data', $data->toDateString())->first();
        if ($folgaManicure && $folgaManicure->dia_todo) return $slots;

        // Horário de funcionamento do salão
        $horarioSalao = $salao->horarios()
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->first();
        if (!$horarioSalao) return $slots;

        // Disponibilidade da manicure
        $dispManicure = $manicure->disponibilidades()
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->first();
        if (!$dispManicure) return $slots;

        $config = ConfiguracaoSalao::paraSalao($salao->id);
        $intervalo = $config?->intervalo_agendamento ?? config('manicure.agenda.intervalo_default', 30);

        // Janela efetiva = interseção entre horário do salão e disponibilidade da manicure
        $inicio = max(
            strtotime($data->toDateString() . ' ' . $horarioSalao->hora_abertura),
            strtotime($data->toDateString() . ' ' . $dispManicure->hora_inicio)
        );
        $fim = min(
            strtotime($data->toDateString() . ' ' . $horarioSalao->hora_fechamento),
            strtotime($data->toDateString() . ' ' . $dispManicure->hora_fim)
        );

        $agendamentosExistentes = Agendamento::where('manicure_id', $manicure->id)
            ->whereDate('data_hora_inicio', $data->toDateString())
            ->whereNotIn('status', [
                AgendamentoStatus::Cancelado->value,
                AgendamentoStatus::NaoCompareceu->value,
            ])
            ->when($agendamentoIgnorar, fn($q) => $q->where('id', '!=', $agendamentoIgnorar))
            ->get(['data_hora_inicio', 'data_hora_fim']);

        // Reservas temporárias (holds) ativas também bloqueiam o slot
        $holdsAtivos = \App\Models\SlotHold::where('manicure_id', $manicure->id)
            ->whereDate('data_hora_inicio', $data->toDateString())
            ->where('expires_at', '>', now())
            ->get(['data_hora_inicio', 'data_hora_fim']);

        $agendamentosExistentes = $agendamentosExistentes->concat($holdsAtivos);

        $cursor = $inicio;
        while ($cursor + ($duracaoTotal * 60) <= $fim) {
            $slotInicio = Carbon::createFromTimestamp($cursor);
            $slotFim = $slotInicio->copy()->addMinutes($duracaoTotal);

            if (!$this->temConflito($agendamentosExistentes, $slotInicio, $slotFim)) {
                $slots->push([
                    'hora' => $slotInicio->format('H:i'),
                    'datetime' => $slotInicio->toDateTimeString(),
                    'disponivel' => true,
                ]);
            }

            $cursor += $intervalo * 60;
        }

        return $slots;
    }

    private function temConflito(Collection $agendamentos, Carbon $inicio, Carbon $fim): bool
    {
        foreach ($agendamentos as $ag) {
            $agInicio = Carbon::parse($ag->data_hora_inicio);
            $agFim = Carbon::parse($ag->data_hora_fim);
            if ($inicio < $agFim && $fim > $agInicio) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cria um agendamento. Aceita DTO tipado ou array (compat retroativa).
     */
    public function criarAgendamento(CriarAgendamentoData|array $dados): Agendamento
    {
        $data = $dados instanceof CriarAgendamentoData ? $dados : CriarAgendamentoData::fromArray($dados);

        return DB::transaction(function () use ($data) {
            $servicos = Servico::whereIn('id', $data->servicoIds)->get();
            $duracaoTotal = (int) $servicos->sum('duracao');
            $valorTotal   = (float) $servicos->sum('preco');

            $manicure = Manicure::findOrFail($data->manicureId);
            $dataHoraInicio = Carbon::parse($data->dataHoraInicio);
            $dataHoraFim    = $dataHoraInicio->copy()->addMinutes($duracaoTotal);

            if ($this->temConflitoNoBanco($manicure->id, $dataHoraInicio, $dataHoraFim)) {
                throw new \RuntimeException('Horário indisponível. Por favor, escolha outro horário.');
            }

            $desconto = 0;
            if ($data->cupomId) {
                $cupom = Cupom::find($data->cupomId);
                if ($cupom && $cupom->isValido()) {
                    $desconto = $cupom->calcularDesconto($valorTotal);
                    $cupom->increment('uso_atual');
                }
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
                'valor_total'      => $valorTotal,
                'valor_desconto'   => $desconto,
                'cupom_id'         => $data->cupomId,
                'nome_cliente'     => $data->nomeCliente,
                'telefone_cliente' => $data->telefoneCliente,
            ]);

            foreach ($servicos as $servico) {
                $agendamento->servicos()->attach($servico->id, [
                    'preco'   => $servico->preco,
                    'duracao' => $servico->duracao,
                ]);
            }

            // Libera reservas temporárias que cobriam este horário
            SlotHold::where('manicure_id', $manicure->id)
                ->where('data_hora_inicio', '<', $dataHoraFim)
                ->where('data_hora_fim', '>', $dataHoraInicio)
                ->delete();

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

        return !SlotHold::where('manicure_id', $manicureId)
            ->where('expires_at', '>', now())
            ->when($excetoToken, fn($q) => $q->where('token', '!=', $excetoToken))
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

        // Remove reservas anteriores do mesmo solicitante
        SlotHold::where('token', $token)->delete();

        if (!$this->slotDisponivel($manicureId, $inicio, $fim, $token)) {
            return null;
        }

        $minutos = (int) config('manicure.agenda.hold_minutos', 10);

        return SlotHold::create([
            'manicure_id'      => $manicureId,
            'data_hora_inicio' => $inicio,
            'data_hora_fim'    => $fim,
            'token'            => $token,
            'expires_at'       => now()->addMinutes($minutos),
        ]);
    }

    public function limparHoldsExpirados(): int
    {
        return SlotHold::where('expires_at', '<', now())->delete();
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
            } catch (\Throwable $e) {
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

            AgendamentoFinalizado::dispatch($agendamento, $comanda);

            return $comanda;
        });
    }

    private function temConflitoNoBanco(int $manicureId, Carbon $inicio, Carbon $fim, ?int $ignorarId = null): bool
    {
        return Agendamento::where('manicure_id', $manicureId)
            ->when($ignorarId, fn($q) => $q->where('id', '!=', $ignorarId))
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
            $agendamento->loadMissing('servicos');

            $duracao = (int) $agendamento->servicos->sum(fn($s) => $s->pivot->duracao);
            if ($duracao <= 0) {
                $duracao = (int) $agendamento->data_hora_inicio->diffInMinutes($agendamento->data_hora_fim);
            }

            $novoFim = $novoInicio->copy()->addMinutes($duracao);

            if ($this->temConflitoNoBanco($agendamento->manicure_id, $novoInicio, $novoFim, $agendamento->id)) {
                throw new \RuntimeException('Horário indisponível. Por favor, escolha outro horário.');
            }

            $dataAnterior = $agendamento->data_hora_inicio->copy();

            $agendamento->update([
                'data_hora_inicio' => $novoInicio,
                'data_hora_fim'    => $novoFim,
            ]);

            \App\Events\AgendamentoReagendado::dispatch($agendamento, $dataAnterior);

            return $agendamento;
        });
    }

    public function getDatasDisponiveis(Manicure $manicure, int $dias = 30): Collection
    {
        $datas = collect();
        $hoje = Carbon::today();
        $config = ConfiguracaoSalao::paraSalao($manicure->salao_id);
        $antecedenciaMinima = $config?->antecedencia_minima ?? config('manicure.agenda.antecedencia_min', 1);
        $antecedenciaMaxima = $config?->antecedencia_maxima ?? config('manicure.agenda.antecedencia_max', 30);

        $diasAtivos = $manicure->disponibilidades()
            ->where('ativo', true)
            ->pluck('dia_semana')
            ->toArray();

        $diasFolga = $manicure->folgas()
            ->where('data', '>=', $hoje->toDateString())
            ->pluck('data')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        $diasFolgaSalao = $manicure->salao->folgas()
            ->where('data', '>=', $hoje->toDateString())
            ->pluck('data')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        for ($i = $antecedenciaMinima; $i <= $antecedenciaMaxima; $i++) {
            $data = $hoje->copy()->addDays($i);
            $diaSemana = (int) $data->format('w');

            if (!in_array($diaSemana, $diasAtivos)) continue;
            if (in_array($data->toDateString(), $diasFolga)) continue;
            if (in_array($data->toDateString(), $diasFolgaSalao)) continue;

            $datas->push($data->toDateString());
        }

        return $datas;
    }
}
