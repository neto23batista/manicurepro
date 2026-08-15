<?php

namespace App\Http\Resources;

use App\Models\Servico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Servico */
class ServicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'nome'              => $this->nome,
            'descricao'         => $this->descricao,
            'imagem_url'        => $this->imagem_url,
            'preco'             => $this->preco,
            'preco_formatado'   => $this->preco_formatado,
            'preco_exibicao'    => $this->preco_exibicao,
            'custo_estimado'    => $this->custo_estimado,
            'duracao'           => $this->duracao,
            'duracao_formatada' => $this->duracao_formatada,
            'combo'             => $this->combo,
            'disponivel_online' => $this->disponivel_online,
            'variacoes'         => $this->whenLoaded('variacoesAtivas', fn () => $this->variacoesAtivas->map(fn ($v) => [
                'id'      => $v->id,
                'nome'    => $v->nome,
                'preco'   => $v->preco,
                'duracao' => $v->duracao,
            ])),
            'categoria' => $this->categoria ? [
                'id'    => $this->categoria->id,
                'nome'  => $this->categoria->nome,
                'cor'   => $this->categoria->cor,
                'icone' => $this->categoria->icone,
            ] : null,
        ];
    }
}
