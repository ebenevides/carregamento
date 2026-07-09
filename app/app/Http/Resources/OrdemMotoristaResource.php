<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdemMotoristaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'ticket_guardian'     => $this->ticket_guardian,
            'produto_codigo'      => $this->produto_codigo,
            'produto_descricao'   => $this->produto_descricao,
            'quantidade_prevista' => $this->quantidade_prevista,
            'placa_veiculo'       => $this->placa_veiculo,
            'placa_carreta'       => $this->placa_carreta,
            'status'              => $this->status->value,
            'status_label'        => $this->status->label(),
            'motorista_nome'      => $this->motorista_nome,
            'motorista_documento' => $this->motorista_documento,
            'pilha_produto'       => $this->whenLoaded('pilhaProduto', fn () => [
                'id'        => $this->pilhaProduto->id,
                'codigo'    => $this->pilhaProduto->codigo,
                'descricao' => $this->pilhaProduto->descricao,
            ]),
            'ponto_carregamento'  => $this->whenLoaded('pontoCarregamento', fn () => [
                'id'        => $this->pontoCarregamento->id,
                'codigo'    => $this->pontoCarregamento->codigo,
                'descricao' => $this->pontoCarregamento->descricao,
            ]),
            'peso_liquido'        => $this->peso_liquido,
            'tara'                => $this->tara,
            'peso_bruto'          => $this->peso_bruto,
            'divergencias_abertas' => $this->when(
                $this->relationLoaded('divergencias'),
                fn () => $this->divergencias->where('status', 'ABERTA')->count()
            ),
        ];
    }
}
