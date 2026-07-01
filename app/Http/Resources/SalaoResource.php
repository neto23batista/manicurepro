<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'slug'            => $this->slug,
            'nome'            => $this->nome,
            'descricao'       => $this->descricao,
            'cidade'          => $this->cidade,
            'estado'          => $this->estado,
            'telefone'        => $this->telefone,
            'whatsapp'        => $this->whatsapp,
            'instagram'       => $this->instagram,
            'logo_url'        => $this->logo_url,
            'endereco'        => $this->whenLoaded('configuracao', fn() => $this->endereco_completo, $this->endereco_completo),
            'nota_media'      => $this->nota_media,
            'manicures_count' => $this->whenCounted('manicures'),
            'ativo'           => (bool) $this->ativo,
        ];
    }
}
