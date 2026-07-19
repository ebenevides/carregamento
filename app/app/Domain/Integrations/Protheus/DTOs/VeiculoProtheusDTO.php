<?php

namespace App\Domain\Integrations\Protheus\DTOs;

final readonly class VeiculoProtheusDTO
{
    public function __construct(
        public ?string $codigo,
        public ?string $placa,
        public ?string $descricao,
        public ?string $municipio,
        public ?string $uf,
        public ?string $chassi,
        public ?string $anoFab,
        public ?string $renavan,
        public ?string $rntc,
    ) {}

    public static function fromArray(array $d): self
    {
        return new self(
            codigo: $d['codigo'] ?? null,
            placa: $d['placa'] ?? null,
            descricao: $d['descricao'] ?? null,
            municipio: $d['municipio'] ?? null,
            uf: $d['uf'] ?? null,
            chassi: ($d['chassi'] ?? '') !== '' ? $d['chassi'] : null,
            anoFab: $d['anoFab'] ?? null,
            renavan: ($d['renavan'] ?? '') !== '' ? $d['renavan'] : null,
            rntc: ($d['rntc'] ?? '') !== '' ? $d['rntc'] : null,
        );
    }
}
