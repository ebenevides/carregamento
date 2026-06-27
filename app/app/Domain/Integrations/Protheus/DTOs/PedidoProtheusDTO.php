<?php

namespace App\Domain\Integrations\Protheus\DTOs;

final readonly class PedidoProtheusDTO
{
    public function __construct(
        public string $numero,
        public string $item,
        public string $filial,
        public string $clienteCodigo,
        public string $clienteLoja,
        public string $clienteNome,
        public string $produtoCodigo,
        public string $produtoDescricao,
        public float $quantidade,
        public string $unidade,
        public ?string $contratoCodigo,
        public ?string $transportadoraCodigo,
        public ?string $transportadoraNome,
        public ?string $placaVeiculo,
        public ?string $motoristaNome,
        public ?string $motoristaDocumento,
        public string $statusComercial,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            numero: $data['numero'],
            item: $data['item'],
            filial: $data['filial'],
            clienteCodigo: $data['cliente_codigo'],
            clienteLoja: $data['cliente_loja'] ?? '01',
            clienteNome: $data['cliente_nome'],
            produtoCodigo: $data['produto_codigo'],
            produtoDescricao: $data['produto_descricao'],
            quantidade: (float) $data['quantidade'],
            unidade: $data['unidade'] ?? 'TN',
            contratoCodigo: $data['contrato_codigo'] ?? null,
            transportadoraCodigo: $data['transportadora_codigo'] ?? null,
            transportadoraNome: $data['transportadora_nome'] ?? null,
            placaVeiculo: $data['placa_veiculo'] ?? null,
            motoristaNome: $data['motorista_nome'] ?? null,
            motoristaDocumento: $data['motorista_documento'] ?? null,
            statusComercial: $data['status_comercial'] ?? 'ABERTO',
        );
    }
}
