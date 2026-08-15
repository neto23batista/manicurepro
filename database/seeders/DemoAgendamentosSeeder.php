<?php

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoAgendamentosSeeder extends Seeder
{
    public const MARKER = 'seed:demo';

    public function run(): void
    {
        $salao = Salao::where('slug', DemoSalaoSeeder::SLUG)->firstOrFail();

        if (Agendamento::where('salao_id', $salao->id)
            ->where('observacoes_internas', self::MARKER)
            ->exists()
        ) {
            $this->command?->info('Agendamentos demo já existem — pulando.');

            return;
        }

        $manicures = Manicure::where('salao_id', $salao->id)->orderBy('id')->get();
        $clientes = Cliente::where('salao_id', $salao->id)->orderBy('id')->get();
        $servicos = Servico::where('salao_id', $salao->id)->where('ativo', true)->orderBy('id')->get();

        if ($manicures->isEmpty() || $clientes->isEmpty() || $servicos->isEmpty()) {
            $this->command?->warn('Faltam manicures/clientes/serviços para seed de agendamentos.');

            return;
        }

        $fernanda = $manicures->firstWhere('email', 'fernanda.profissional@fernandasilvanails.com') ?? $manicures->first();
        $camila = $manicures->firstWhere('email', 'camila@fernandasilvanails.com') ?? $manicures->skip(1)->first() ?? $fernanda;
        $juliana = $manicures->firstWhere('email', 'juliana@fernandasilvanails.com') ?? $manicures->skip(2)->first() ?? $fernanda;

        $byName = fn (string $nome) => $servicos->firstWhere('nome', $nome) ?? $servicos->first();

        // Passados: slots fixos sem sobreposição por profissional
        $passados = [
            [-14, $fernanda, $clientes[0], $byName('Manicure Simples'), 10, 0, 'concluido', 'web'],
            [-14, $camila, $clientes[1], $byName('Pedicure'), 11, 0, 'concluido', 'balcao'],
            [-12, $fernanda, $clientes[2], $byName('Esmaltação em Gel'), 14, 0, 'concluido', 'web'],
            [-10, $juliana, $clientes[3], $byName('Blindagem'), 11, 0, 'concluido', 'telefone'],
            [-9, $camila, $clientes[4], $byName('Manicure + Pedicure'), 9, 30, 'cancelado', 'web'],
            [-7, $fernanda, $clientes[0], $byName('Alongamento em Gel'), 10, 0, 'concluido', 'app'],
            [-7, $camila, $clientes[5], $byName('Spa dos Pés'), 14, 0, 'nao_compareceu', 'web'],
            [-5, $juliana, $clientes[6], $byName('Esmaltação em Gel'), 15, 0, 'concluido', 'web'],
            [-3, $fernanda, $clientes[7] ?? $clientes[0], $byName('Manicure Simples'), 9, 0, 'concluido', 'balcao'],
            [-2, $camila, $clientes[1], $byName('Pedicure'), 10, 30, 'concluido', 'web'],
            [-1, $fernanda, $clientes[0], $byName('Combo Premium (Mãos + Pés + Spa)'), 13, 0, 'concluido', 'web'],
        ];

        foreach ($passados as [$dias, $manicure, $cliente, $servico, $hora, $minuto, $status, $origem]) {
            $data = $this->proximoDiaUtil(now()->copy()->startOfDay()->addDays($dias));
            $this->criar($salao, $manicure, $cliente, $servico, $data, $hora, $minuto, $status, $origem);
        }

        // Futuros (próximos dias úteis)
        $futuros = [
            [1, $fernanda, $clientes[0], $byName('Esmaltação em Gel'), 10, 0, 'confirmado', 'web'],
            [1, $camila, $clientes[2], $byName('Manicure Simples'), 11, 0, 'confirmado', 'app'],
            [2, $juliana, $clientes[3], $byName('Blindagem'), 14, 0, 'aguardando', 'web'],
            [3, $fernanda, $clientes[4], $byName('Alongamento em Fibra'), 9, 0, 'confirmado', 'telefone'],
            [3, $camila, $clientes[5], $byName('Pedicure'), 15, 0, 'confirmado', 'web'],
            [5, $fernanda, $clientes[0], $byName('Manicure + Pedicure'), 11, 30, 'confirmado', 'web'],
            [7, $juliana, $clientes[6], $byName('Nail Art (por unha)'), 10, 0, 'confirmado', 'balcao'],
        ];

        foreach ($futuros as [$dias, $manicure, $cliente, $servico, $hora, $minuto, $status, $origem]) {
            $data = $this->proximoDiaUtil(now()->copy()->startOfDay()->addDays($dias));
            // Ajusta sábado para horário de funcionamento
            if ((int) $data->format('w') === 6 && $hora >= 15) {
                $hora = 10;
                $minuto = 0;
            }
            $this->criar($salao, $manicure, $cliente, $servico, $data, $hora, $minuto, $status, $origem);
        }
    }

    private function proximoDiaUtil(Carbon $data): Carbon
    {
        while ((int) $data->format('w') === 0) {
            $data->addDay();
        }

        return $data;
    }

    private function criar(
        Salao $salao,
        Manicure $manicure,
        Cliente $cliente,
        Servico $servico,
        Carbon $data,
        int $hora,
        int $minuto,
        string $status,
        string $origem
    ): void {
        $inicio = $data->copy()->setTime($hora, $minuto, 0);
        $fim = $inicio->copy()->addMinutes($servico->duracao);

        $ag = Agendamento::create([
            'salao_id'             => $salao->id,
            'cliente_id'           => $cliente->id,
            'manicure_id'          => $manicure->id,
            'user_id'              => $cliente->user_id,
            'data_hora_inicio'     => $inicio,
            'data_hora_fim'        => $fim,
            'status'               => $status,
            'valor_total'          => $servico->preco,
            'valor_desconto'       => 0,
            'origem'               => $origem,
            'observacoes_internas' => self::MARKER,
            'confirmado_em'        => in_array($status, ['confirmado', 'concluido', 'em_andamento'], true) ? $inicio->copy()->subDay() : null,
        ]);

        $ag->servicos()->syncWithoutDetaching([
            $servico->id => [
                'preco'   => $servico->preco,
                'duracao' => $servico->duracao,
            ],
        ]);

        if ($status === 'concluido') {
            $cliente->increment('total_visitas');
            $cliente->increment('total_gasto', (float) $servico->preco);
            $cliente->increment('pontos_fidelidade', (int) floor((float) $servico->preco));
        }
    }
}
