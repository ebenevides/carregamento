<?php

namespace App\Domain\Integrations\Protheus\DTOs;

final readonly class ItemPedidoProtheusDTO
{
    public function __construct(
        public string $item,
        public ?string $codigo,
        public string $produto,
        public float $quantidade,
        public ?float $precoUnitario,
        public ?float $valorTotal,
        public ?string $dataFaturamento,
        public ?string $nota,
        public ?string $serie,
        public ?string $contrato,
        public ?string $tes,
        public ?string $armazem,
        public ?string $entrega,
        public ?VeiculoProtheusDTO $veiculo,
        public ?MotoristaProtheusDTO $motorista,
    ) {}

    public static function fromArray(array $d): self
    {
        return new self(
            item: $d['item'],
            codigo: $d['codigo'] ?? null,
            produto: $d['produto'],
            quantidade: (float) $d['quantidade'],
            precoUnitario: isset($d['precoUnitario']) ? (float) $d['precoUnitario'] : null,
            valorTotal: isset($d['valorTotal']) ? (float) $d['valorTotal'] : null,
            dataFaturamento: $d['dataFaturamento'] ?? null,
            nota: ($d['nota'] ?? '') !== '' ? $d['nota'] : null,
            serie: $d['serie'] ?? null,
            contrato: ($d['contrato'] ?? '') !== '' ? $d['contrato'] : null,
            tes: $d['tes'] ?? null,
            armazem: $d['armazem'] ?? null,
            entrega: $d['entrega'] ?? null,
            veiculo: !empty($d['veiculo']) ? VeiculoProtheusDTO::fromArray($d['veiculo']) : null,
            motorista: !empty($d['motorista']) ? MotoristaProtheusDTO::fromArray($d['motorista']) : null,
        );
    }
}
