<?php

namespace App\Domain\Carregamento\DTOs;

final readonly class CriarOrdemDTO
{
    public function __construct(
        public string $produtoCodigo,
        public float $quantidadePrevista,
        public string $placaVeiculo,
        public ?string $empresa = null,
        public ?string $filial = null,
        public ?string $pedidoNumero = null,
        public ?string $pedidoItem = null,
        public ?string $contratoCodigo = null,
        public ?string $ticketGuardian = null,
        public ?string $clienteCodigo = null,
        public ?string $clienteLoja = null,
        public ?string $clienteNome = null,
        public ?string $produtoDescricao = null,
        public string $unidade = 'TN',
        public ?string $placaCarreta = null,
        public ?string $motoristaNome = null,
        public ?string $motoristaDocumento = null,
        public ?string $transportadoraCodigo = null,
        public ?string $transportadoraNome = null,
        public ?float $tara = null,
        public float $toleranciaPercentual = 5.0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            produtoCodigo: $data['produto_codigo'],
            quantidadePrevista: $data['quantidade_prevista'],
            placaVeiculo: $data['placa_veiculo'],
            empresa: $data['empresa'] ?? null,
            filial: $data['filial'] ?? null,
            pedidoNumero: $data['pedido_numero'] ?? null,
            pedidoItem: $data['pedido_item'] ?? null,
            contratoCodigo: $data['contrato_codigo'] ?? null,
            ticketGuardian: $data['ticket_guardian'] ?? null,
            clienteCodigo: $data['cliente_codigo'] ?? null,
            clienteLoja: $data['cliente_loja'] ?? null,
            clienteNome: $data['cliente_nome'] ?? null,
            produtoDescricao: $data['produto_descricao'] ?? null,
            unidade: $data['unidade'] ?? 'TN',
            placaCarreta: $data['placa_carreta'] ?? null,
            motoristaNome: $data['motorista_nome'] ?? null,
            motoristaDocumento: $data['motorista_documento'] ?? null,
            transportadoraCodigo: $data['transportadora_codigo'] ?? null,
            transportadoraNome: $data['transportadora_nome'] ?? null,
            tara: isset($data['tara']) ? (float) $data['tara'] : null,
            toleranciaPercentual: $data['tolerancia_percentual'] ?? 5.0,
        );
    }
}
