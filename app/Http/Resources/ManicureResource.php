<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManicureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'salao_id'   => $this->salao_id,
            'nome'       => $this->nome,
            'bio'        => $this->bio,
            'foto_url'   => $this->foto_url,
            'nota_media' => $this->nota_media,
            'ativo'      => (bool) $this->ativo,
        ];
    }
}
