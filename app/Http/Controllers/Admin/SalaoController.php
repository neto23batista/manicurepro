<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalaoRequest;
use App\Http\Requests\UpdateSalaoRequest;
use App\Models\ConfiguracaoSalao;
use App\Models\HorarioFuncionamento;
use App\Models\Salao;
use Illuminate\Support\Str;

class SalaoController extends Controller
{
    public function index()
    {
        // Single-tenant: a listagem vai direto para a edição do salão único.
        $salao = Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        return redirect()->route('admin.saloes.edit', $salao);
    }

    public function create()
    {
        return view('admin.saloes.create');
    }

    public function store(StoreSalaoRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['nome']) . '-' . Str::random(4);

        $salao = Salao::create($data);

        ConfiguracaoSalao::create(['salao_id' => $salao->id]);

        for ($dia = 1; $dia <= 6; $dia++) {
            HorarioFuncionamento::create([
                'salao_id'        => $salao->id,
                'dia_semana'      => $dia,
                'hora_abertura'   => '08:00:00',
                'hora_fechamento' => '18:00:00',
                'ativo'           => $dia <= 5,
            ]);
        }

        return redirect()->route('admin.saloes.show', $salao)
            ->with('success', 'Salão criado com sucesso!');
    }

    public function show(Salao $salao)
    {
        $salao->load(['manicures', 'clientes', 'configuracao', 'horarios']);
        $agendamentosHoje = $salao->agendamentos()->whereDate('data_hora_inicio', today())->count();
        $faturamentoMes = $salao->agendamentos()
            ->concluidos()
            ->doMes()
            ->sum('valor_total');
        return view('admin.saloes.show', compact('salao', 'agendamentosHoje', 'faturamentoMes'));
    }

    public function edit(Salao $salao)
    {
        return view('admin.saloes.edit', compact('salao'));
    }

    public function update(UpdateSalaoRequest $request, Salao $salao)
    {
        $salao->update($request->validated());

        return redirect()->route('admin.saloes.show', $salao)
            ->with('success', 'Salão atualizado com sucesso!');
    }

    public function destroy(Salao $salao)
    {
        $salao->update(['ativo' => false]);
        return redirect()->route('admin.saloes.index')
            ->with('success', 'Salão desativado com sucesso!');
    }
}
