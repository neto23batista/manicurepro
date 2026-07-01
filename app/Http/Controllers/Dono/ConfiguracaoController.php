<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfigSalaoRequest;
use App\Http\Requests\UpdateDadosSalaoRequest;
use App\Http\Requests\UpdateHorariosRequest;
use App\Models\ConfiguracaoSalao;
use App\Models\HorarioFuncionamento;
use Illuminate\Support\Facades\Storage;

class ConfiguracaoController extends Controller
{
    public function edit()
    {
        $salao = auth()->user()->salao;
        $config = $salao->configuracao ?? ConfiguracaoSalao::create(['salao_id' => $salao->id]);
        $horarios = $salao->horarios()->orderBy('dia_semana')->get()->keyBy('dia_semana');

        return view('dono.configuracao.edit', compact('salao', 'config', 'horarios'));
    }

    public function updateDados(UpdateDadosSalaoRequest $request)
    {
        $salao = auth()->user()->salao;
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $this->deleteIfExists($salao->logo);
            $data['logo'] = $request->file('logo')->store('saloes/logos', 'public');
        }

        if ($request->hasFile('foto_capa')) {
            $this->deleteIfExists($salao->foto_capa);
            $data['foto_capa'] = $request->file('foto_capa')->store('saloes/capas', 'public');
        }

        $salao->update($data);

        return back()->with('success', 'Dados do salão atualizados!');
    }

    public function destroyLogo()
    {
        $salao = auth()->user()->salao;
        $this->deleteIfExists($salao->logo);
        $salao->update(['logo' => null]);
        return back()->with('success', 'Logo removida.');
    }

    public function destroyCapa()
    {
        $salao = auth()->user()->salao;
        $this->deleteIfExists($salao->foto_capa);
        $salao->update(['foto_capa' => null]);
        return back()->with('success', 'Capa removida.');
    }

    public function updateHorarios(UpdateHorariosRequest $request)
    {
        $salao = auth()->user()->salao;

        foreach ($request->horarios as $dia => $dados) {
            HorarioFuncionamento::updateOrCreate(
                ['salao_id' => $salao->id, 'dia_semana' => $dia],
                [
                    'ativo'           => ($dados['ativo'] ?? '0') == '1',
                    'hora_abertura'   => $dados['hora_abertura'] ?? '09:00',
                    'hora_fechamento' => $dados['hora_fechamento'] ?? '18:00',
                ]
            );
        }

        return back()->with('success', 'Horários de funcionamento atualizados!');
    }

    public function updateConfig(UpdateConfigSalaoRequest $request)
    {
        $salao = auth()->user()->salao;

        $data = $request->validated();
        $data['permitir_agendamento_online'] = $request->boolean('permitir_agendamento_online');
        $data['fidelidade_ativo']            = $request->boolean('fidelidade_ativo');
        $data['notificar_email']             = $request->boolean('notificar_email');
        $data['notificar_whatsapp']          = $request->boolean('notificar_whatsapp');

        $config = $salao->configuracao ?? ConfiguracaoSalao::create(['salao_id' => $salao->id]);
        $config->update($data);

        ConfiguracaoSalao::esquecerCache($salao->id);

        return back()->with('success', 'Configurações atualizadas!');
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
