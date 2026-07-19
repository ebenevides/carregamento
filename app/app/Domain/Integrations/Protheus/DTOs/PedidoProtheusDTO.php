<?php

namespace App\Domain\Integrations\Protheus\DTOs;

final readonly class PedidoProtheusDTO
{
    /** @param ItemPedidoProtheusDTO[] $itens */
    public function __construct(
        public string $filial,
        public string $numero,
        public ?string $tipoPedido,
        public ?string $emissao,
        public ?string $condicaoPagamento,
        public ?string $vendedor,
        public ?string $transportadoraCodigo,
        public ?string $transportadoraNome,
        public ?string $mensagemNota,
        public ClienteProtheusDTO $cliente,
        public array $itens,
    ) {}

    public static function fromArray(array $d): self
    {
        return new self(
            filial: $d['filial'],
            numero: $d['numero'],
            tipoPedido: $d['tipoPedido'] ?? null,
            emissao: $d['emissao'] ?? null,
            condicaoPagamento: $d['condicaoPagamento'] ?? null,
            vendedor: ($d['vendedor'] ?? '') !== '' ? $d['vendedor'] : null,
            transportadoraCodigo: ($d['codTransp'] ?? '') !== '' ? $d['codTransp'] : null,
            transportadoraNome: ($d['transportadora'] ?? '') !== '' ? $d['transportadora'] : null,
            mensagemNota: ($d['mensagemNota'] ?? '') !== '' ? $d['mensagemNota'] : null,
            cliente: ClienteProtheusDTO::fromArray($d['cliente']),
            itens: array_map(
                fn (array $item) => ItemPedidoProtheusDTO::fromArray($item),
                $d['itens'] ?? []
            ),
        );
    }

    /** Busca um item pelo número (ordens_carregamento.pedido_item) */
    public function item(string $numeroItem): ?ItemPedidoProtheusDTO
    {
        foreach ($this->itens as $item) {
            if ($item->item === $numeroItem) {
                return $item;
            }
        }

        return null;
    }

    public function primeiroItem(): ?ItemPedidoProtheusDTO
    {
        return $this->itens[0] ?? null;
    }
}
