<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdemCarregamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'empresa'               => $this->empresa,
            'filial'                => $this->filial,
            'pedido_numero'         => $this->pedido_numero,
            'pedido_item'           => $this->pedido_item,
            'contrato_codigo'       => $this->contrato_codigo,
            'ticket_guardian'       => $this->ticket_guardian,
            'cliente_codigo'        => $this->cliente_codigo,
            'cliente_loja'          => $this->cliente_loja,
            'cliente_nome'          => $this->cliente_nome,
            'produto_codigo'        => $this->produto_codigo,
            'produto_descricao'     => $this->produto_descricao,
            'quantidade_prevista'   => (float) $this->quantidade_prevista,
            'unidade'               => $this->unidade,
            'placa_veiculo'         => $this->placa_veiculo,
            'placa_carreta'         => $this->placa_carreta,
            'motorista_nome'        => $this->motorista_nome,
            'motorista_documento'   => $this->motorista_documento,
            'transportadora_codigo' => $this->transportadora_codigo,
            'transportadora_nome'   => $this->transportadora_nome,
            'tara'                  => $this->tara !== null ? (float) $this->tara : null,
            'peso_bruto'            => $this->peso_bruto !== null ? (float) $this->peso_bruto : null,
            'peso_liquido'          => $this->peso_liquido !== null ? (float) $this->peso_liquido : null,
            'tolerancia_percentual' => (float) $this->tolerancia_percentual,
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'iniciado_em'           => $this->iniciado_em?->toISOString(),
            'concluido_em'          => $this->concluido_em?->toISOString(),
            'pesagem_final_em'      => $this->pesagem_final_em?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),
            'pilha_produto'         => $this->whenLoaded('pilhaProduto', fn () => [
                'id'      => $this->pilhaProduto->id,
                'codigo'  => $this->pilhaProduto->codigo,
                'descricao' => $this->pilhaProduto->descricao,
            ]),
            'ponto_carregamento'    => $this->whenLoaded('pontoCarregamento', fn () => [
                'id'      => $this->pontoCarregamento->id,
                'codigo'  => $this->pontoCarregamento->codigo,
                'descricao' => $this->pontoCarregamento->descricao,
            ]),
            'operador'              => $this->whenLoaded('operador', fn () => [
                'id'   => $this->operador->id,
                'name' => $this->operador->name,
            ]),
            'divergencias_abertas'  => $this->when(
                $this->relationLoaded('divergencias'),
                fn () => $this->divergencias->where('status', 'ABERTA')->count()
            ),
            'eventos'               => $this->whenLoaded('eventos', fn () =>
                $this->eventos->map(fn ($e) => [
                    'tipo'           => $e->tipo->value,
                    'tipo_label'     => $e->tipo->label(),
                    'status_anterior' => $e->status_anterior?->value,
                    'status_novo'    => $e->status_novo?->value,
                    'origem'         => $e->origem->value,
                    'usuario_nome'   => $e->usuario_nome,
                    'observacao'     => $e->observacao,
                    'ocorrido_em'    => $e->ocorrido_em->toISOString(),
                ])
            ),
        ];
    }
}
