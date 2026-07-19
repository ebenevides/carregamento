<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Integrations\Guardian\Adapters\GuardianAdapterInterface;
use App\Domain\Integrations\Protheus\Adapters\ProtheusAdapterInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class IntegracaoController extends Controller
{
    public function __construct(
        private readonly ProtheusAdapterInterface $protheus,
        private readonly GuardianAdapterInterface $guardian,
    ) {}

    public function pedidoProtheus(string $numero): JsonResponse
    {
        $pedido = $this->protheus->consultarPedido($numero, request()->input('filial', '01'));

        return response()->json([
            'filial'                => $pedido->filial,
            'numero'                => $pedido->numero,
            'tipo_pedido'           => $pedido->tipoPedido,
            'emissao'               => $pedido->emissao,
            'condicao_pagamento'    => $pedido->condicaoPagamento,
            'vendedor'              => $pedido->vendedor,
            'transportadora_codigo' => $pedido->transportadoraCodigo,
            'transportadora_nome'   => $pedido->transportadoraNome,
            'cliente'               => [
                'codigo'             => $pedido->cliente->codigo,
                'loja'               => $pedido->cliente->loja,
                'nome'               => $pedido->cliente->nome,
                'nome_fantasia'      => $pedido->cliente->nomeFantasia,
                'cnpj'               => $pedido->cliente->cnpj,
                'cidade'             => $pedido->cliente->cidade,
                'estado'             => $pedido->cliente->estado,
            ],
            'itens' => array_map(fn ($item) => [
                'item'         => $item->item,
                'produto'      => $item->produto,
                'quantidade'   => $item->quantidade,
                'preco_unitario' => $item->precoUnitario,
                'valor_total'  => $item->valorTotal,
                'contrato'     => $item->contrato,
                'nota'         => $item->nota,
                'placa_veiculo' => $item->veiculo?->placa,
                'motorista_nome' => $item->motorista?->nome,
                'motorista_documento' => $item->motorista?->cpf,
            ], $pedido->itens),
        ]);
    }

    public function ticketGuardian(string $ticket): JsonResponse
    {
        $dto = $this->guardian->consultarTicket($ticket);

        return response()->json([
            'ticket'       => $dto->ticket,
            'status'       => $dto->status,
            'placa'        => $dto->placa,
            'motorista'    => $dto->motorista,
            'tara_kg'      => $dto->taraKg(),
            'peso_bruto_kg' => $dto->pesoBrutoKg(),
            'peso_liquido_kg' => $dto->pesoLiquidoKg(),
            'data_entrada' => $dto->dataEntrada,
            'data_saida'   => $dto->dataSaida,
        ]);
    }

    public function filaGuardian(string $ticket): JsonResponse
    {
        $dto = $this->guardian->consultarFila($ticket, request()->input('placa'));

        return response()->json([
            'ticket'            => $dto->ticket,
            'sucesso'           => $dto->sucesso(),
            'descricao'         => $dto->descricao,
            'placa'             => $dto->placa,
            'posicao'           => $dto->posicao,
            'estado'            => $dto->estado,
            'estado_descricao'  => $dto->estadoDescricao,
            'liberado'          => $dto->liberado(),
            'fila_id'           => $dto->filaId,
            'fila_codigo'       => $dto->filaCodigo,
            'fila_nome'         => $dto->filaNome,
            'fila_mensagem'     => $dto->filaMensagem,
            'mensagem_usuario'  => $dto->mensagemUsuario,
            'data_atualizacao'  => $dto->dataAtualizacao,
        ]);
    }
}
