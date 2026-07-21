<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PontoCarregamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'codigo'           => $this->codigo,
            'descricao'        => $this->descricao,
            'unidade_britagem' => $this->unidade_britagem,
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'observacao'       => $this->observacao,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
