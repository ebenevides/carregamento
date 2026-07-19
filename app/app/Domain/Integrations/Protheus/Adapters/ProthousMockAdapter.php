<?php

namespace App\Domain\Integrations\Protheus\Adapters;

use App\Domain\Integrations\Protheus\DTOs\PedidoProtheusDTO;
use Illuminate\Validation\ValidationException;

class ProthousMockAdapter implements ProtheusAdapterInterface
{
    private array $pedidos = [
        '781456' => [
            'filial'            => '01',
            'numero'            => '781456',
            'tipoPedido'        => 'Normal',
            'emissao'           => '2026-06-26',
            'condicaoPagamento' => '001',
            'vendedor'          => '',
            'codTransp'         => '000001',
            'transportadora'    => 'Transportadora Exemplo',
            'mensagemNota'      => '',
            'cliente'           => [
                'codigo'            => '017687',
                'loja'              => '01',
                'nome'              => 'Cliente Exemplo Ltda',
                'nomeFantasia'      => 'Cliente Exemplo',
                'cnpj'              => '12345678000199',
                'inscricaoEstadual' => '123456789',
                'tipo'              => 'Consumidor Final',
                'tipoPessoa'        => 'J',
                'endereco'          => 'Rua Exemplo, 100',
                'bairro'            => 'Centro',
                'cidade'            => 'Cuiaba',
                'estado'            => 'MT',
                'cep'               => '78000000',
                'telefone'          => '6533334444',
                'email'             => 'contato@clienteexemplo.com.br',
            ],
            'itens' => [
                [
                    'item'           => '01',
                    'codigo'         => '000013',
                    'produto'        => 'BRITA1',
                    'quantidade'     => 32.0,
                    'precoUnitario'  => 65.0,
                    'valorTotal'     => 2080.0,
                    'dataFaturamento' => null,
                    'nota'           => '',
                    'serie'          => '1',
                    'contrato'       => '000123',
                    'tes'            => '511',
                    'armazem'        => '01',
                    'entrega'        => '2026-06-26',
                    'veiculo'        => [
                        'codigo'     => 'Z0000001',
                        'placa'      => 'ABC1D23',
                        'descricao'  => 'M.BENZ/ATRON 1635 S',
                        'municipio'  => 'Cuiaba',
                        'uf'         => 'MT',
                        'chassi'     => '',
                        'anoFab'     => '2020',
                        'renavan'    => '',
                        'rntc'       => '',
                    ],
                    'motorista' => [
                        'codigo'        => '000196',
                        'nome'          => 'João da Silva',
                        'numCNH'        => '00000000000',
                        'categoriaCNH'  => 'AE',
                        'registroCNH'   => '00000000000',
                        'vencimentoCNH' => '2030-01-01',
                        'email'         => '',
                        'telefone'      => '',
                        'CPF'           => '00000000000',
                        'RG'            => '',
                        'emissorRG'     => '',
                    ],
                ],
            ],
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
