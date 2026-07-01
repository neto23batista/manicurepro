<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco' => $this->preco,
            'preco_formatado' => $this->preco_formatado,
            'duracao' => $this->duracao,
            'duracao_formatada' => $this->duracao_formatada,
            'combo' => $this->combo,
            'disponivel_online' => $this->disponivel_online,
            'categoria' => $this->categoria ? [
                'id' => $this->categoria->id,
                'nome' => $this->categoria->nome,
                'cor' => $this->categoria->cor,
                'icone' => $this->categoria->icone,
            ] : null,
        ];
    }
}
