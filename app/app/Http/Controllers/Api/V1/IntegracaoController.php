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
            'numero'               => $pedido->numero,
            'item'                 => $pedido->item,
            'filial'               => $pedido->filial,
            'cliente_codigo'       => $pedido->clienteCodigo,
            'cliente_loja'         => $pedido->clienteLoja,
            'cliente_nome'         => $pedido->clienteNome,
            'produto_codigo'       => $pedido->produtoCodigo,
            'produto_descricao'    => $pedido->produtoDescricao,
            'quantidade'           => $pedido->quantidade,
            'unidade'              => $pedido->unidade,
            'contrato_codigo'      => $pedido->contratoCodigo,
            'transportadora_codigo' => $pedido->transportadoraCodigo,
            'transportadora_nome'  => $pedido->transportadoraNome,
            'placa_veiculo'        => $pedido->placaVeiculo,
            'motorista_nome'       => $pedido->motoristaNome,
            'motorista_documento'  => $pedido->motoristaDocumento,
            'status_comercial'     => $pedido->statusComercial,
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
}
