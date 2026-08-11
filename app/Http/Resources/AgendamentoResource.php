<?php

namespace App\Http\Resources;

use App\Models\Agendamento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Agendamento */
class AgendamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'status'               => $this->status,
            'status_label'         => $this->status_label,
            'status_color'         => $this->status_color,
            'data_hora_inicio'     => $this->data_hora_inicio->toISOString(),
            'data_hora_fim'        => $this->data_hora_fim->toISOString(),
            'data_formatada'       => $this->data_hora_inicio->format('d/m/Y'),
            'hora_formatada'       => $this->data_hora_inicio->format('H:i'),
            'valor_total'          => $this->valor_total,
            'valor_desconto'       => $this->valor_desconto,
            'valor_liquido'        => (float) $this->valor_total - (float) $this->valor_desconto,
            'observacoes'          => $this->observacoes,
            'origem'               => $this->origem,
            'nome_cliente_exibido' => $this->nome_cliente_exibido,
            'salao'                => $this->whenLoaded('salao', fn () => [
                'id'       => $this->salao->id,
                'slug'     => $this->salao->slug,
                'nome'     => $this->salao->nome,
                'endereco' => $this->salao->endereco_completo,
            ]),
            'manicure' => $this->whenLoaded('manicure', fn () => [
                'id'         => $this->manicure->id,
                'nome'       => $this->manicure->nome,
                'foto_url'   => $this->manicure->foto_url,
                'nota_media' => $this->manicure->nota_media,
            ]),
            'servicos' => $this->whenLoaded('servicos', fn () => $this->servicos->map(fn ($s) => [
                'id'      => $s->id,
                'nome'    => $s->nome,
                'preco'   => $s->pivot->preco ?? $s->preco,
                'duracao' => $s->pivot->duracao ?? $s->duracao,
            ])->values()),
            'criado_em' => $this->created_at?->toISOString(),
        ];
    }
}
