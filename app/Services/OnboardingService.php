<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\ConfiguracaoSalao;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;

/**
 * Wizard de 1º acesso + checklist no dashboard do dono.
 */
class OnboardingService
{
    /**
     * @return list<array{key: string, label: string, done: bool, route: string|null}>
     */
    public function checklist(Salao $salao): array
    {
        $temHorario = HorarioFuncionamento::where('salao_id', $salao->id)
            ->where('ativo', true)
            ->exists();

        $temManicure = Manicure::where('salao_id', $salao->id)->where('ativo', true)->exists();
        $temServico = Servico::where('salao_id', $salao->id)->where('ativo', true)->exists();
        $temAgendamento = Agendamento::where('salao_id', $salao->id)->exists();

        $dadosOk = filled($salao->nome)
            && filled($salao->telefone)
            && filled($salao->cidade);

        return [
            [
                'key'   => 'dados',
                'label' => 'Completar dados do salão (nome, telefone, cidade)',
                'done'  => $dadosOk,
                'route' => 'dono.config.edit',
            ],
            [
                'key'   => 'horarios',
                'label' => 'Definir horários de funcionamento',
                'done'  => $temHorario,
                'route' => 'dono.config.edit',
            ],
            [
                'key'   => 'manicure',
                'label' => 'Ter ao menos uma manicure ativa',
                'done'  => $temManicure,
                'route' => null, // cadastro via painel admin
            ],
            [
                'key'   => 'servico',
                'label' => 'Ter ao menos um serviço ativo',
                'done'  => $temServico,
                'route' => null,
            ],
            [
                'key'   => 'agendamento',
                'label' => 'Criar o primeiro agendamento',
                'done'  => $temAgendamento,
                'route' => 'dono.agendamentos.create',
            ],
        ];
    }

    public function progress(Salao $salao): array
    {
        $items = $this->checklist($salao);
        $done = count(array_filter($items, fn ($i) => $i['done']));
        $total = count($items);

        return [
            'items'    => $items,
            'done'     => $done,
            'total'    => $total,
            'percent'  => $total > 0 ? (int) round(($done / $total) * 100) : 100,
            'complete' => $done === $total,
        ];
    }

    public function shouldShowChecklist(Salao $salao, ?ConfiguracaoSalao $config): bool
    {
        if (! $config) {
            return true;
        }

        if ($config->onboarding_completed_at || $config->onboarding_dismissed_at) {
            return false;
        }

        return ! $this->progress($salao)['complete'];
    }

    public function shouldForceWizard(Salao $salao, ?ConfiguracaoSalao $config): bool
    {
        if (! $config || $config->onboarding_completed_at || $config->onboarding_dismissed_at) {
            return false;
        }

        $progress = $this->progress($salao);

        // Só força o wizard quando o salão está praticamente vazio (0 itens).
        // Com 1+ itens (ex.: factory com dados), o checklist no dashboard basta.
        return $progress['done'] === 0;
    }

    public function markCompleted(ConfiguracaoSalao $config): void
    {
        $config->update([
            'onboarding_completed_at' => now(),
            'onboarding_dismissed_at' => null,
        ]);
        ConfiguracaoSalao::esquecerCache((int) $config->salao_id);
    }

    public function dismiss(ConfiguracaoSalao $config): void
    {
        $config->update(['onboarding_dismissed_at' => now()]);
        ConfiguracaoSalao::esquecerCache((int) $config->salao_id);
    }
}
