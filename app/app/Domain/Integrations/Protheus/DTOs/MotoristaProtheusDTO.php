<?php

namespace App\Domain\Integrations\Protheus\DTOs;

final readonly class MotoristaProtheusDTO
{
    public function __construct(
        public ?string $codigo,
        public ?string $nome,
        public ?string $numCnh,
        public ?string $categoriaCnh,
        public ?string $registroCnh,
        public ?string $vencimentoCnh,
        public ?string $email,
        public ?string $telefone,
        public ?string $cpf,
        public ?string $rg,
        public ?string $emissorRg,
    ) {}

    public static function fromArray(array $d): self
    {
        return new self(
            codigo: $d['codigo'] ?? null,
            nome: $d['nome'] ?? null,
            numCnh: $d['numCNH'] ?? null,
            categoriaCnh: $d['categoriaCNH'] ?? null,
            registroCnh: $d['registroCNH'] ?? null,
            vencimentoCnh: $d['vencimentoCNH'] ?? null,
            email: ($d['email'] ?? '') !== '' ? $d['email'] : null,
            telefone: ($d['telefone'] ?? '') !== '' ? $d['telefone'] : null,
            cpf: $d['CPF'] ?? null,
            rg: ($d['RG'] ?? '') !== '' ? $d['RG'] : null,
            emissorRg: ($d['emissorRG'] ?? '') !== '' ? $d['emissorRG'] : null,
        );
    }
}
