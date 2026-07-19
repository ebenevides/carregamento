<?php

namespace App\Domain\Integrations\Protheus\DTOs;

final readonly class ClienteProtheusDTO
{
    public function __construct(
        public string $codigo,
        public string $loja,
        public string $nome,
        public ?string $nomeFantasia,
        public ?string $cnpj,
        public ?string $inscricaoEstadual,
        public ?string $tipo,
        public ?string $tipoPessoa,
        public ?string $endereco,
        public ?string $bairro,
        public ?string $cidade,
        public ?string $estado,
        public ?string $cep,
        public ?string $telefone,
        public ?string $email,
    ) {}

    public static function fromArray(array $d): self
    {
        return new self(
            codigo: $d['codigo'],
            loja: $d['loja'] ?? '01',
            nome: $d['nome'],
            nomeFantasia: $d['nomeFantasia'] ?? null,
            cnpj: $d['cnpj'] ?? null,
            inscricaoEstadual: $d['inscricaoEstadual'] ?? null,
            tipo: $d['tipo'] ?? null,
            tipoPessoa: $d['tipoPessoa'] ?? null,
            endereco: $d['endereco'] ?? null,
            bairro: $d['bairro'] ?? null,
            cidade: $d['cidade'] ?? null,
            estado: $d['estado'] ?? null,
            cep: $d['cep'] ?? null,
            telefone: $d['telefone'] ?? null,
            email: $d['email'] ?? null,
        );
    }
}
