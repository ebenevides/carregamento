<?php

namespace App\Domain\Integrations\Protheus\Adapters;

use App\Domain\Integrations\Protheus\DTOs\PedidoProtheusDTO;
use Illuminate\Validation\ValidationException;

class ProthousMockAdapter implements ProtheusAdapterInterface
{
    private array $pedidos = [
        '781456' => [
            'numero'               => '781456',
            'item'                 => '01',
            'filial'               => '01',
            'cliente_codigo'       => '017687',
            'cliente_loja'         => '01',
            'cliente_nome'         => 'Cliente Exemplo Ltda',
            'produto_codigo'       => 'BRITA1',
            'produto_descricao'    => 'Brita 1',
            'quantidade'           => 32.0,
            'unidade'              => 'TN',
            'contrato_codigo'      => '000123',
            'transportadora_codigo' => '000001',
            'transportadora_nome'  => 'Transportadora Exemplo',
            'placa_veiculo'        => 'ABC1D23',
            'motorista_nome'       => 'João da Silva',
            'motorista_documento'  => '00000000000',
            'status_comercial'     => 'ABERTO',
        ],
    ];

    public function consultarPedido(string $numero, string $filial): PedidoProtheusDTO
    {
        $pedido = $this->pedidos[$numero] ?? null;

        if ($pedido === null) {
            throw ValidationException::withMessages([
                'pedido' => "Pedido {$numero} não encontrado no Protheus (mock).",
            ]);
        }

        return PedidoProtheusDTO::fromArray($pedido);
    }

    public function pedidoExiste(string $numero, string $filial): bool
    {
        return isset($this->pedidos[$numero]);
    }
}
