<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PilhaProdutoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'codigo'              => $this->codigo,
            'descricao'           => $this->descricao,
            'produto_codigo'      => $this->produto_codigo,
            'produto_descricao'   => $this->produto_descricao,
            'ativa'               => $this->ativa,
            'observacao'          => $this->observacao,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
