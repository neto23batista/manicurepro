<?php

namespace Database\Seeders;

use App\Models\CategoriaServico;
use App\Models\Cupom;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\Servico;
use Illuminate\Database\Seeder;

class DemoCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $salao = Salao::where('slug', DemoSalaoSeeder::SLUG)->firstOrFail();

        $categorias = [
            'Manicure'              => ['cor' => '#e91e8c', 'icone' => 'fa-hand-sparkles', 'ordem' => 1],
            'Pedicure'              => ['cor' => '#9c27b0', 'icone' => 'fa-spa', 'ordem' => 2],
            'Tratamentos Especiais' => ['cor' => '#e91e8c', 'icone' => 'fa-star', 'ordem' => 3],
            'Combos'                => ['cor' => '#ff6b9d', 'icone' => 'fa-gift', 'ordem' => 4],
        ];

        $catIds = [];
        foreach ($categorias as $nome => $meta) {
            $cat = CategoriaServico::updateOrCreate(
                ['salao_id' => $salao->id, 'nome' => $nome],
                array_merge($meta, ['ativo' => true]),
            );
            $catIds[$nome] = $cat->id;
        }

        $servicos = [
            ['nome' => 'Manicure Simples', 'categoria' => 'Manicure', 'preco' => 30.00, 'duracao' => 30, 'combo' => false, 'descricao' => 'Corte, lixamento e esmaltação tradicional.'],
            ['nome' => 'Pedicure', 'categoria' => 'Pedicure', 'preco' => 40.00, 'duracao' => 45, 'combo' => false, 'descricao' => 'Cuidados com unhas e cutículas dos pés.'],
            ['nome' => 'Manicure + Pedicure', 'categoria' => 'Combos', 'preco' => 65.00, 'duracao' => 75, 'combo' => true, 'descricao' => 'Combo mãos e pés com esmaltação.'],
            ['nome' => 'Esmaltação em Gel', 'categoria' => 'Tratamentos Especiais', 'preco' => 55.00, 'duracao' => 60, 'combo' => false, 'descricao' => 'Esmaltação em gel de longa duração.'],
            ['nome' => 'Alongamento em Fibra', 'categoria' => 'Tratamentos Especiais', 'preco' => 120.00, 'duracao' => 120, 'combo' => false, 'descricao' => 'Alongamento com fibra de vidro.'],
            ['nome' => 'Alongamento em Gel', 'categoria' => 'Tratamentos Especiais', 'preco' => 130.00, 'duracao' => 120, 'combo' => false, 'descricao' => 'Alongamento estruturado em gel.'],
            ['nome' => 'Nail Art (por unha)', 'categoria' => 'Tratamentos Especiais', 'preco' => 10.00, 'duracao' => 30, 'combo' => false, 'descricao' => 'Decoração artística por unha.'],
            ['nome' => 'Remoção de Gel/Alongamento', 'categoria' => 'Manicure', 'preco' => 35.00, 'duracao' => 30, 'combo' => false, 'descricao' => 'Remoção segura de gel ou alongamento.'],
            ['nome' => 'Spa dos Pés', 'categoria' => 'Pedicure', 'preco' => 55.00, 'duracao' => 60, 'combo' => false, 'descricao' => 'Esfoliação, hidratação e massagem nos pés.'],
            ['nome' => 'Blindagem', 'categoria' => 'Tratamentos Especiais', 'preco' => 45.00, 'duracao' => 45, 'combo' => false, 'descricao' => 'Fortalecimento e proteção das unhas naturais.'],
            ['nome' => 'Combo Premium (Mãos + Pés + Spa)', 'categoria' => 'Combos', 'preco' => 150.00, 'duracao' => 120, 'combo' => true, 'descricao' => 'Experiência completa mãos, pés e spa.'],
        ];

        foreach ($servicos as $s) {
            Servico::updateOrCreate(
                ['salao_id' => $salao->id, 'nome' => $s['nome']],
                [
                    'categoria_id'      => $catIds[$s['categoria']],
                    'descricao'         => $s['descricao'],
                    'preco'             => $s['preco'],
                    'duracao'           => $s['duracao'],
                    'combo'             => $s['combo'],
                    'disponivel_online' => true,
                    'ativo'             => true,
                ],
            );
        }

        $produtos = [
            ['nome' => 'Esmalte Risqué Nude', 'codigo' => 'ESM-001', 'marca' => 'Risqué', 'preco_custo' => 4.50, 'preco_venda' => 12.00, 'estoque_atual' => 24, 'estoque_minimo' => 5, 'unidade' => 'un'],
            ['nome' => 'Esmalte Risqué Vermelho', 'codigo' => 'ESM-002', 'marca' => 'Risqué', 'preco_custo' => 4.50, 'preco_venda' => 12.00, 'estoque_atual' => 18, 'estoque_minimo' => 5, 'unidade' => 'un'],
            ['nome' => 'Base Coat Gel', 'codigo' => 'GEL-001', 'marca' => 'Inocos', 'preco_custo' => 28.00, 'preco_venda' => 55.00, 'estoque_atual' => 8, 'estoque_minimo' => 3, 'unidade' => 'un'],
            ['nome' => 'Top Coat Gel', 'codigo' => 'GEL-002', 'marca' => 'Inocos', 'preco_custo' => 30.00, 'preco_venda' => 58.00, 'estoque_atual' => 3, 'estoque_minimo' => 4, 'unidade' => 'un'],
            ['nome' => 'Removedor Acetona', 'codigo' => 'REM-001', 'marca' => 'Farmax', 'preco_custo' => 6.00, 'preco_venda' => 15.00, 'estoque_atual' => 2, 'estoque_minimo' => 5, 'unidade' => 'un'],
            ['nome' => 'Lixa 180/240', 'codigo' => 'LIX-001', 'marca' => 'Genérica', 'preco_custo' => 0.80, 'preco_venda' => 3.50, 'estoque_atual' => 50, 'estoque_minimo' => 10, 'unidade' => 'un'],
            ['nome' => 'Kit Cutícula', 'codigo' => 'KIT-001', 'marca' => 'Mundial', 'preco_custo' => 12.00, 'preco_venda' => 29.90, 'estoque_atual' => 12, 'estoque_minimo' => 3, 'unidade' => 'un'],
            ['nome' => 'Óleo de Cutícula', 'codigo' => 'OLE-001', 'marca' => 'Dailus', 'preco_custo' => 8.00, 'preco_venda' => 22.00, 'estoque_atual' => 15, 'estoque_minimo' => 4, 'unidade' => 'un'],
            ['nome' => 'Algodão Hidrófilo 50g', 'codigo' => 'ALG-001', 'marca' => 'Genérica', 'preco_custo' => 3.00, 'preco_venda' => 8.00, 'estoque_atual' => 1, 'estoque_minimo' => 2, 'unidade' => 'pct'],
            ['nome' => 'Fibra de Vidro (rolo)', 'codigo' => 'FIB-001', 'marca' => 'Genie Nails', 'preco_custo' => 18.00, 'preco_venda' => 45.00, 'estoque_atual' => 6, 'estoque_minimo' => 2, 'unidade' => 'un'],
        ];

        foreach ($produtos as $p) {
            Produto::updateOrCreate(
                ['salao_id' => $salao->id, 'codigo' => $p['codigo']],
                array_merge($p, [
                    'descricao' => $p['nome'],
                    'ativo'     => true,
                ]),
            );
        }

        $cupons = [
            [
                'codigo'          => 'BEMVINDA',
                'tipo'            => 'percentual',
                'valor'           => 15,
                'minimo_pedido'   => 50,
                'maximo_desconto' => 30,
                'uso_maximo'      => 100,
                'validade'        => now()->addMonths(3)->toDateString(),
            ],
            [
                'codigo'          => 'FIDELIDADE10',
                'tipo'            => 'percentual',
                'valor'           => 10,
                'minimo_pedido'   => 40,
                'maximo_desconto' => 25,
                'uso_maximo'      => null,
                'validade'        => now()->addMonths(6)->toDateString(),
            ],
            [
                'codigo'          => 'DESCONTO20',
                'tipo'            => 'fixo',
                'valor'           => 20,
                'minimo_pedido'   => 80,
                'maximo_desconto' => null,
                'uso_maximo'      => 50,
                'validade'        => now()->addMonths(2)->toDateString(),
            ],
            [
                'codigo'          => 'ANIVERSARIO',
                'tipo'            => 'percentual',
                'valor'           => 25,
                'minimo_pedido'   => 0,
                'maximo_desconto' => 40,
                'uso_maximo'      => 200,
                'validade'        => now()->addYear()->toDateString(),
            ],
        ];

        foreach ($cupons as $c) {
            Cupom::updateOrCreate(
                ['codigo' => $c['codigo']],
                array_merge($c, [
                    'salao_id' => $salao->id,
                    'ativo'    => true,
                ]),
            );
        }
    }
}
