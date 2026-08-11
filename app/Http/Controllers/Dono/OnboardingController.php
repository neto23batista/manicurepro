<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoSalao;
use App\Services\OnboardingService;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(private OnboardingService $onboarding) {}

    public function show()
    {
        $user = auth()->user();
        abort_unless($user->isDono() || $user->isSuperAdmin(), 403);

        $salao = $user->salao;
        abort_unless($salao, 404);

        $config = $salao->configuracao ?? ConfiguracaoSalao::create(['salao_id' => $salao->id]);
        $progress = $this->onboarding->progress($salao);

        return view('dono.onboarding.wizard', compact('salao', 'config', 'progress'));
    }

    public function complete(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isDono() || $user->isSuperAdmin(), 403);

        $salao = $user->salao;
        abort_unless($salao, 404);

        $config = $salao->configuracao ?? ConfiguracaoSalao::create(['salao_id' => $salao->id]);
        $this->onboarding->markCompleted($config);

        return redirect()
            ->route('dono.dashboard')
            ->with('success', 'Configuração inicial concluída! Bom trabalho.');
    }

    public function dismiss(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isDono() || $user->isSuperAdmin(), 403);

        $salao = $user->salao;
        abort_unless($salao, 404);

        $config = $salao->configuracao ?? ConfiguracaoSalao::create(['salao_id' => $salao->id]);
        $this->onboarding->dismiss($config);

        return redirect()
            ->route('dono.dashboard')
            ->with('success', 'Checklist ocultado. Você pode reabrir o guia em Configurações.');
    }
}
